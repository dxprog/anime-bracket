<?php

namespace Api {

    use Lib;
    use stdClass;

    define('BS_NOT_STARTED', 0);
    define('BS_NOMINATIONS', 1);
    define('BS_ELIMINATIONS', 2);
    define('BS_VOTING', 3);
    define('BS_WILDCARD', 4);
    define('BS_FINAL', 5);
    define('BS_HIDDEN', 6);

    class Bracket extends Lib\Dal {

        /**
         * Object property to table column map
         */
        protected $_dbMap = array(
            'id' => 'bracket_id',
            'name' => 'bracket_name',
            'perma' => 'bracket_perma',
            'start' => 'bracket_start',
            'state' => 'bracket_state',
            'pic' => 'bracket_pic',
            'winnerCharacterId' => 'winner_character_id',
            'rules' => 'bracket_rules',
            'source' => 'bracket_source',
            'advanceHour' => 'bracket_advance_hour',
            'nameLabel' => 'bracket_name_label',
            'sourceLabel' => 'bracket_source_label',
            'score' => 'bracket_score',
            'externalId' => 'bracket_external_id',
            'minAge' => 'bracket_min_age'
        );

        /**
         * Database table
         */
        protected $_dbTable = 'bracket';

        /**
         * Primary key
         */
        protected $_dbPrimaryKey = 'id';

        /**
         * Bracket ID
         */
        public $id = 0;

        /**
         * Bracket name
         */
        public $name = 0;

        /**
         * Perma ID of the bracket
         */
        public $perma;

        /**
         * Bracket start date
         */
        public $start;

        /**
         * Bracket state
         */
        public $state;

        /**
         * Bracket picture
         */
        public $pic;

        /**
         * Character ID of the bracket winner
         */
        public $winnerCharacterId;

        /**
         * Character object of winner
         */
        public $winner;

        /**
         * Bracket rules
         */
        public $rules;

        /**
         * Source ID of the bracket
         */
        public $source = BRACKET_SOURCE;

        /**
         * Whether this bracket should auto advance and at what hour to do so
         */
        public $advanceHour;

        /**
         * The label for the "name" box in nominations
         */
        public $nameLabel;

        /**
         * The label for the "source" box in nominations
         */
        public $sourceLabel;

        /**
         * The bracket's popularity score
         */
        public $score;

        /**
         * ID to the latest reddit post
         */
        public $externalId;

        /**
         * The age a reddit account must be to vote
         */
        public $minAge;

        /**
         * Override for getAll to include the winner character object
         */
        public static function getAll($force = false) {
            $cacheKey = 'Api:Bracket:getAll_' . BRACKET_SOURCE;
            $retVal = Lib\Cache::Get($cacheKey);
            if (false === $retVal || $force) {
                $brackets = parent::queryReturnAll([ 'source' => BRACKET_SOURCE, 'state' => [ 'ne' => BS_HIDDEN ] ], [ 'score' => 'desc', 'state' => 'desc', 'start' => 'desc' ]);
                $retVal = [];
                foreach ($brackets as $bracket) {
                    if ($bracket->winnerCharacterId) {
                        $bracket->winner = Character::getById($bracket->winnerCharacterId);
                    }

                    if ($bracket->start <= time() || $force) {
                        $retVal[] = $bracket;
                    }
                }
                Lib\Cache::Set($cacheKey, $retVal, 3600);
            }
            return $retVal;
        }

        /**
         * Returns brackets owned by the passed user
         */
        public static function getUserOwnedBrackets($user, $force = false) {
            $retVal = null;

            if ($user instanceof User) {
                $cacheKey = 'Api:Bracket:getUserOwnedBrackets_' . implode('_', [ $user->id, BRACKET_SOURCE ]);
                $retVal = Lib\Cache::get($cacheKey);
                if (false === $retVal || $force) {

                    // Admins get all the fun
                    if ($user->admin) {
                        $retVal = self::getAll();
                    } else {
                        $result = Lib\Db::Query('SELECT * FROM bracket WHERE bracket_source = :source AND bracket_id IN (SELECT bracket_id FROM bracket_owners WHERE user_id = :userId)', [ ':source' => BRACKET_SOURCE, ':userId' => $user->id ]);
                        if ($result && $result->count) {
                            $retVal = [];
                            while ($row = Lib\Db::Fetch($result)) {
                                $retVal[] = new Bracket($row);
                            }
                        }
                    }

                    Lib\Cache::set($cacheKey, $retVal);

                }
            }

            return $retVal;
        }

        /**
         * Gets a bracket by perma lookup
         */
        public static function getBracketByPerma($perma, $force = false) {
            $cacheKey = 'Api:Bracket:getBracketByPerma_' . $perma;
            $retVal = Lib\Cache::Get($cacheKey);
            if (false === $retVal || $force) {
                $result = Lib\Db::Query('SELECT * FROM `bracket` WHERE `bracket_perma` = :perma', [ ':perma' => $perma ]);
                if ($result && $result->count) {
                    $retVal = new Bracket(Lib\Db::Fetch($result));
                }
                Lib\Cache::Set($cacheKey, $retVal);
            }
            return $retVal;
        }

        /**
         * Returns the current results and rounds of the bracket
         */
        public function getResults($force = false) {
            $cacheKey = 'Api:Bracket:getResults_' . $this->id;

            return Lib\Cache::fetchLongCache(function() {

                $retVal = [];

                // Calculate the number of tiers in the bracket
                $row = Lib\Db::Fetch(Lib\Db::Query('SELECT SUM(CASE WHEN round_tier = 1 THEN 1 ELSE 0 END) AS total, MAX(round_tier) AS max_tier, MAX(round_group) AS max_group FROM round WHERE bracket_id = :bracketId AND round_tier > 0', [ ':bracketId' => $this->id ]));
                $groups = 1 + (int) $row->max_group;
                $baseRounds = (int) $row->total;

                $i = $baseRounds * 2;
                $tiers = 0;
                while ($i > 1) {
                    $i /= 2;
                    $tiers++;
                }

                // More bullshit that needs to be done to support that ONE BRACKET that had wildcards
                $dbTiers = (int) $row->max_tier;
                $tiers = $dbTiers > $tiers ? $dbTiers : $tiers;

                for ($i = 0; $i < $tiers; $i++) {

                    $rounds = Round::getRoundsByTier($this->id, $i + 1);
                    $roundCount = count($rounds);
                    $groupRoundCount = $baseRounds / $groups;

                    for ($j = 0; $j < $baseRounds; $j++) {
                        if ($j < $roundCount) {

                            // Numericize where needed
                            $round = $rounds[$j];
                            $round->id = (int) $round->id;
                            $round->order = (int) $round->order;
                            $round->group = (int) $round->group;
                            $round->tier = (int) $round->tier;
                            $round->character1->id = (int) $round->character1->id;
                            $round->character2->id = (int) $round->character2->id;

                            if ($round->final) {
                                $round->character1->votes = (int) $round->character1Votes;
                                $round->character2->votes = (int) $round->character2Votes;
                            }

                            // Toss out some extraneous data
                            unset($round->bracketId);
                            unset($round->character1Id);
                            unset($round->character2Id);
                            unset($round->character1->bracketId);
                            unset($round->character2->bracketId);
                            unset($round->voted);

                        } else {
                            $round = new stdClass;
                            $round->tier = $i + 1;
                            $round->order = $j;
                            $round->group = floor($j / $groupRoundCount);
                            $round->filler = true;
                            $rounds[] = $round;
                        }

                    }

                    $retVal[] = $rounds;
                    $baseRounds /= 2;
                }

                return $retVal;

            }, $cacheKey, $force);

        }

        /**
         * Returns the votes of a given user for this bracket
         * @param User $user The user to fetch votes for
         * @param bool $force Bypass cache and force read from DB
         */
        public function getVotesForUser(User $user, $force = false) {
            $retVal = null;
            if ($user instanceof User) {
                $cacheKey = 'Api:Bracket:getVotesForUser_' . $this->id . '_' . $user->id;
                $retVal = Lib\Cache::fetchLongCache(function() use ($user) {
                    $params = [ ':userId' => $user->id, ':bracketId' => $this->id ];
                    $result = Lib\Db::Query('SELECT round_id, character_id FROM votes WHERE user_id = :userId AND bracket_id = :bracketId', $params);
                    $retVal = [];
                    if ($result && $result->count) {
                        while ($row = Lib\Db::Fetch($result)) {
                            $retVal[$row->round_id] = (int) $row->character_id;
                        }
                    }
                    return $retVal;
                }, $cacheKey, $force);
            }
            return $retVal;
        }

        /**
         * Advances the bracket to the next tier/group
         */
        public function advance() {

            // Lock the bracket
            $cacheKey = $this->_lockedCacheKey();
            Lib\Cache::set($cacheKey, true, CACHE_MEDIUM);

            switch ($this->state) {
                case BS_ELIMINATIONS:
                    $this->_advanceEliminations();
                    break;
                case BS_VOTING:
                    $this->_advanceBracket();
                    Stats::getEntrantPerformanceStats($this, true);
                    break;
            }

            // Force update various cached things
            $this->getResults(true);
            Round::getCurrentRounds($this->id, true);

            // Unlock. Keep this on a short cache since it'll default back to false anyways
            Lib\Cache::set($cacheKey, false, CACHE_SHORT);

        }

        /**
         * Returns the locked status of the bracket
         */
        public function isLocked() {
            return Lib\Cache::get($this->_lockedCacheKey());
        }

        /**
         * Advances the elmination round to the next group or the bracket proper
         */
        private function _advanceEliminations() {

            // Get a list of rounds not yet completed
            $result = Lib\Db::Query('SELECT MIN(round_group) AS current_group FROM `round` WHERE bracket_id = :bracketId AND round_final != 1 ORDER BY round_group', [ ':bracketId' => $this->id ]);
            if ($result && $result->count) {
                $row = Lib\Db::Fetch($result);
                Lib\Db::Query('UPDATE `round` SET round_final = 1 WHERE bracket_id = :bracketId AND round_group = :group', [ ':bracketId' => $this->id, ':group' => $row->current_group ]);
            }

        }

        /**
         * Advances a standard bracket tier
         */
        private function _advanceBracket() {

            $rounds = Round::getCurrentRounds($this->id, true);
            if (count($rounds) > 1) {
                for ($i = 0, $count = count($rounds); $i < $count; $i += 2) {

                    // Get the round winners
                    $winner1 = $rounds[$i]->getWinnerId();
                    $winner2 = $rounds[$i + 1]->getWinnerId();

                    // Create the round for the next tier
                    $newRound = new Round();
                    $newRound->bracketId = $this->id;
                    $newRound->tier = $rounds[$i]->tier + 1;
                    $newRound->group = $rounds[$i]->group;
                    $newRound->order = $i / 2;
                    $newRound->character1Id = $winner1;
                    $newRound->character2Id = $winner2;
                    $newRound->sync();

                    // Finalize the current tier
                    $rounds[$i]->getVoteCount();
                    $rounds[$i]->final = true;
                    $rounds[$i]->sync();
                    $rounds[$i + 1]->getVoteCount();
                    $rounds[$i + 1]->final = true;
                    $rounds[$i + 1]->sync();

                }
            } else if (count($rounds) === 1) {
                $round = $rounds[0];
                $round->getVoteCount();
                $round->final = true;
                $round->sync();

                $this->winner = $round->getWinner();
                $this->winnerCharacterId = $this->winner->id;
                $this->state = BS_FINAL;
                $this->sync();

            }

            // Clear the results cache
            Lib\Cache::Set('Api:Bracket:getResults_' . $this->id, false, 1);

        }

        /**
         * Takes the results from the elimination rounds and creates a seeded bracket
         */
        public function createBracketFromEliminations($entrants, $groups) {

            $retVal = false;

            if (is_numeric($entrants)) {

                // Generate the bracket template
                $seeding = self::generateSeededBracket($entrants);

                // Get the max vote counts for each day
                $result = Lib\Db::Query('SELECT COUNT(1) AS total, r.round_group FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE v.bracket_id = :bracketId GROUP BY r.round_group', [ ':bracketId' => $this->id ]);
                $groupCounts = [];
                $max = 0;
                while ($row = Lib\Db::Fetch($result)) {
                    $votes = (int) $row->total;
                    $groupCounts[(int) $row->round_group] = $votes;
                    $max = $votes > $max ? $votes : $max;
                }

                $characters = [];
                $result = Lib\Db::Query('SELECT COUNT(1) AS total, c.*, r.round_group FROM `round` r INNER JOIN `character` c ON c.character_id = r.round_character1_id LEFT OUTER JOIN votes v ON v.character_id = c.character_id WHERE r.round_tier = 0 AND r.bracket_id = :bracketId GROUP BY c.character_id', [ ':bracketId' => $this->id ]);

                // Ensure that we have characters and there are at least enough to meet the bracket constraints
                if ($result && $result->count >= $entrants) {
                    while ($row = Lib\Db::Fetch($result)) {
                        $obj = new Character($row);

                        // Normalize the votes against the highest day of voting to ensure that seeding order is reflective of flucuations in daily voting
                        // $obj->adjustedVotes = round(($obj->votes / $groups[$obj->group]) * $max);
                        $obj->adjustedVotes = round(((int) $row->total / $groupCounts[(int) $row->round_group]) * $max);

                        $characters[] = $obj;
                    }

                    // Reorder by adjusted votes
                    usort($characters, function($a, $b) {
                        return $a->adjustedVotes < $b->adjustedVotes ? 1 : -1;
                    });

                    // Set up the rounds
                    $groupSplit = $entrants / $groups;
                    for ($i = 0; $i < $entrants; $i += 2) {
                        $round = new Round();
                        $round->bracketId = $this->id;
                        $round->tier = 1;
                        $round->order = ($i + 1) % $groupSplit;
                        $round->group = floor($i / $groupSplit);

                        // Get the correct character and save their seed
                        $character1 = $characters[$seeding[$i] - 1];
                        $character1->seed = $seeding[$i];
                        $character1->sync();
                        $character2 = $characters[$seeding[$i + 1] - 1];
                        $character2->seed = $seeding[$i + 1];
                        $character2->sync();

                        $round->character1Id = $character1->id;
                        $round->character2Id = $character2->id;
                        $round->sync();

                    }

                    // Change the state to standard bracket voting
                    $this->state = BS_VOTING;
                    $retVal = $this->sync();

                    // Force update the results cache
                    $this->getResults(true);

                }

            }

            return $retVal;

        }

        /**
         * Generates the ordering for the first tier of a seeded bracket
         */
        public static function generateSeededBracket($entrants, array $buildFrom = null) {
            $entrants = (int) $entrants;
            $buildFrom = is_array($buildFrom) ? $buildFrom : [ 1, 2 ];
            $retVal = [];
            for ($i = 0, $count = count($buildFrom); $i < $count; $i++) {
                $retVal[] = $buildFrom[$i];
                $retVal[] = $count * 2 - ($buildFrom[$i] - 1);
            }
            return count($retVal) === $entrants ? $retVal : self::generateSeededBracket($entrants, $retVal);
        }

        /**
         * Generates a perma link for this bracket
         */
        public function generatePerma() {
            $perma = preg_replace('/[^A-Za-z0-9\-]+/i', '-', $this->name);
            $perma = str_replace('--', '-', $perma);
            $this->perma = $perma = strtolower($perma);

            // Make sure this doesn't share a perma with another bracket
            $permaOkay = false;
            $counter = 0;
            while (!$permaOkay) {
                $result = Lib\Db::Query('SELECT bracket_id FROM `bracket` WHERE `bracket_perma` = :perma', [ ':perma' => $perma ]);
                if ($result && $result->count) {
                    $counter++;
                    $perma = $this->perma . '-' . $counter;
                } else {
                    $permaOkay = true;
                    $this->perma = $perma;
                }
            }

        }

        /**
         * Returns whether or not this bracket has a source label
         */
        public function hasSourceLabel() {
            return $this->sourceLabel !== 'NO_SOURCE';
        }

        /**
         * Assigns this bracket to a user
         */
        public function addUser(User $user) {
            return Lib\Db::Query('INSERT INTO bracket_owners VALUES (:bracketId, :userId)', [ ':bracketId' => $this->id, ':userId' => $user->id ]);
        }

        /**
         * Generator for the locked cache key
         */
        private function _lockedCacheKey() {
            return 'Api:Bracket:bracket_locked_' . $this->id;
        }

    }

}
