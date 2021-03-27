<?php

namespace Api {

    use Lib;
    use stdClass;

    define('STATS_CACHE_DURATION', 30);

    class Stats {

        public static function getVotesOverTime($vars) {
            return self::_getVotesOverTime($vars);
        }

        public static function getUsersVotingOverTime($vars) {
            return self::_getVotesOverTime($vars, true);
        }

        private static function _getVotesOverTime($vars, $users = false) {

            $time = time();
            $startDate = Lib\Url::GetInt('startDate', $time - 3600 * 24, $vars); // Default to the last 24 hours
            $endDate = Lib\Url::GetInt('endDate', $time, $vars);
            $bracketId = Lib\Url::GetInt('bracketId', null, $vars);
            $granularity = Lib\Url::GetInt('granularity', 2, $vars);
            $cache = Lib\Cache::getInstance();
            $cacheKey = '_getVotesOverTime_' . implode('_', [ $startDate, $endDate, $bracketId, $granularity, $users ]);
            $retVal = $cache->get($cacheKey);

            if (false === $retVal && $bracketId) {

                $selectCount = $users ? 'DISTINCT user_id' : '1';
                $result = Lib\Db::Query('SELECT COUNT(' . $selectCount . ') AS total, DATE(FROM_UNIXTIME(vote_date)) AS date, HOUR(FROM_UNIXTIME(vote_date)) AS hour, (MINUTE(FROM_UNIXTIME(vote_date)) % :granularity) AS hour_fraction FROM votes WHERE bracket_id = :bracketId AND vote_date BETWEEN :start AND :end GROUP BY date, hour, hour_fraction ORDER BY date, hour, hour_fraction',
                    [ ':granularity' => $granularity, ':bracketId' => $bracketId, ':start' => $startDate, ':end' => $endDate ]);
                if ($result && $result->count) {
                    $retVal = [];
                    while ($row = Lib\Db::Fetch($result)) {
                        $obj = new stdClass;
                        $obj->date = (int) $row->date;
                        $obj->hour = (int) $row->hour;
                        $obj->minutes = $row->hour_fraction == 0 ? 0 : 60 * ((int) $row->hour_fraction / $granularity);
                        $obj->count = (int) $row->total;
                        $retVal[] = $obj;
                    }
                    $cache->set($cacheKey, $retVal, STATS_CACHE_DURATION);
                }

            }

            return $retVal;

        }

        /**
         * Generates a list of characters in a bracket (ordered by seed) and their performance
         * in said bracket
         */
        public static function getEntrantPerformanceStats(Bracket $bracket, $force = false) {

            return Lib\Cache::getInstance()->fetchLongCache(function() use ($bracket) {
                // Get all tourney rounds and characters for this bracket
                $characters = Character::queryReturnAll([ 'bracketId' => $bracket->id, 'seed' => [ 'null' => false ] ], [ 'seed' => 'asc' ]);
                $rounds = Round::queryReturnAll([ 'bracketId' => $bracket->id, 'final' => 1, 'tier' => [ 'gt' => 0 ] ], [ 'id' => 'asc' ]);

                // If no final rounds came back (aka, we've just started round 1, group A), get the non-final rounds
                if (!count($rounds)) {
                    $rounds = Round::queryReturnAll([ 'bracketId' => $bracket->id, 'tier' => [ 'gt' => 0 ] ], [ 'id' => 'asc' ]);
                }

                // Create a hash out of the characters
                $temp = [];
                foreach ($characters as $character) {
                    $temp[$character->id] = $character;
                }
                $characters = $temp;

                // Sort the rounds out based on character for faster access later
                $characterRounds = [];
                foreach ($rounds as $round) {
                    // Decorate the round with full character models
                    $round->character1 = $characters[$round->character1Id];
                    $round->character2 = $characters[$round->character2Id];
                    self::_addRoundToCharacterRounds($round, $round->character1Id, $characterRounds);
                    self::_addRoundToCharacterRounds($round, $round->character2Id, $characterRounds);
                }

                $retVal = [];
                foreach ($characters as $character) {
                    $closestDiff = -1;
                    $closestRound = null;
                    $lostTo = null;
                    $totalVotes = 0;
                    $characterGroup = 0;

                    if (isset($characterRounds[$character->id])) {
                        $roundsForCharacter = array_values($characterRounds[$character->id]);
                        $characterGroup = $roundsForCharacter[0]->group;
                        foreach ($roundsForCharacter as $round) {
                            // Heheheh... so gross
                            $isCharacter1 = $round->character1Id == $character->id;
                            $totalVotes += $isCharacter1 ? $round->character1Votes : $round->character2Votes;

                            $diff = abs($round->character1Votes - $round->character2Votes);
                            if (($diff < $closestDiff || $closestDiff === -1) && $round->final) {
                                $closestDiff = $diff;
                                // This case should be small enough that re-instantiating through a loop
                                // shouldn't prove too much of a performance concern (especially since
                                // it's generated only once per new round). Will monitor in production
                                $closestRound = (object)[
                                    'character' => $isCharacter1 ? $round->character2 : $round->character1,
                                    'difference' => $closestDiff,
                                    'round' => $round
                                ];
                            }

                            $lost = ($isCharacter1 && $round->character1Votes < $round->character2Votes) || (!$isCharacter1 && $round->character2Votes < $round->character1Votes);
                            $lostTo = $lost ? (object)[
                                'character' => $isCharacter1 ? $round->character2 : $round->character1,
                                'lostBy' => $diff,
                                'round' => $round
                            ] : null;

                        }
                    }

                    $retVal[] = (object)[
                        'character' => $character,
                        'closestRound' => $closestRound,
                        'lostTo' => $lostTo,
                        'totalVotes' => $totalVotes,
                        'group' => chr(65 + $characterGroup)
                    ];

                }

                return $retVal;

            }, 'Stats::PerformanceStats_' . $bracket->id, $force);

        }

        private static function _addRoundToCharacterRounds(Round $round, $characterId, array &$characterRounds) {
            if (!isset($characterRounds[$characterId])) {
                $characterRounds[$characterId] = [];
            }
            $characterRounds[$characterId][$round->character1Id . '_' . $round->character2Id] = $round;
        }

    }

}
