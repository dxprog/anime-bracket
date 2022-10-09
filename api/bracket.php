<?php

namespace Api {

    use Lib;
    use stdClass;
    use Error;

    define('BS_NOT_STARTED', 0);
    define('BS_NOMINATIONS', 1);
    define('BS_ELIMINATIONS', 2);
    define('BS_VOTING', 3);
    define('BS_WILDCARD', 4);
    define('BS_FINAL', 5);
    define('BS_HIDDEN', 6);

    class Bracket extends Lib\Dal {

        public static $CAPTCHA_STATUS = [
            'NEVER' => 0,
            'RANDOM' => 1,
            'ALWAYS' => 2
        ];

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
            'minAge' => 'bracket_min_age',
            'hidden' => 'bracket_hidden',
            'blurb' => 'bracket_blurb',
            'captcha' => 'bracket_captcha'
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
         * If this bracket is up for publically viewable
         */
        public $hidden;

        /**
         * Short blurb about the bracket
         */
        public $blurb;

        /**
         * The captcha setting for this bracket
         */
        public $captcha;

        /**
         * Ints and bools things
         * @override
         */
        public function copyFromDbRow($row) {
            parent::copyFromDbRow($row);
            if ($row) {
                $this->id = (int) $this->id;
                $this->state = (int) $this->state;
                $this->start = (int) $this->start;
                $this->winnerCharacterId = $this->winnerCharacterId ? (int) $this->winnerCharacterId : null;
                $this->hidden = $this->hidden > 0 ? 1 : 0;
            }
        }

        /**
         * Override for getAll to include the winner character object
         */
        public static function getAll($force = false, $getHidden = false) {
            $cache = Lib\Cache::getInstance();
            $cacheKey = 'Api:Bracket:getAll_' . BRACKET_SOURCE;
            $retVal = $cache->get($cacheKey);
            if (false === $retVal || $force) {
                $query = [ 'source' => BRACKET_SOURCE, 'state' => [ 'ne' => BS_HIDDEN ] ];
                if (!$getHidden) {
                    $query['hidden'] = 0;
                }

                $brackets = parent::queryReturnAll($query, [ 'score' => 'desc', 'state' => 'desc', 'start' => 'desc' ]);
                $retVal = [];
                foreach ($brackets as $bracket) {
                    if ($bracket->winnerCharacterId) {
                        $bracket->winner = Character::getById($bracket->winnerCharacterId);
                    }

                    if ($bracket->start <= time() || $force) {
                        $retVal[] = $bracket;
                    }
                }
                $cache->set($cacheKey, $retVal, 3600);
            }
            return $retVal;
        }

        /**
         * Returns brackets owned by the passed user
         */
        public static function getUserOwnedBrackets($user, $force = false, $allBrackets = false) {
            $retVal = null;

            if ($user instanceof User) {
                $cacheKey = 'Api:Bracket:getUserOwnedBrackets_' . implode('_', [ $user->id, BRACKET_SOURCE, $allBrackets ? 'user' : 'all' ]);
                $cache = Lib\Cache::getInstance();
                $retVal = $cache->get($cacheKey);
                if (false === $retVal || $force) {

                    // Admins get all the fun
                    if ($user->admin && $allBrackets) {
                        $retVal = self::getAll(false, true);
                    } else {
                        $result = Lib\Db::Query('CALL proc_GetUserBrackets(:source, :userId)', [
                            ':source' => BRACKET_SOURCE,
                            ':userId' => $user->id
                        ]);
                        if ($result && $result->count) {
                            $retVal = [];
                            while ($row = Lib\Db::Fetch($result)) {
                                $retVal[] = new Bracket($row);
                            }
                        }
                    }

                    $cache->set($cacheKey, $retVal);

                }
            }

            return $retVal;
        }

        /**
         * Gets a bracket by perma lookup
         */
        public static function getBracketByPerma($perma, $force = false) {
            $cache = Lib\Cache::getInstance();
            $cacheKey = 'Api:Bracket:getBracketByPerma_' . $perma;
            $retVal = $cache->get($cacheKey);
            if (false === $retVal || $force) {
                $result = self::queryReturnAll([ 'perma' => $perma ]);
                $retVal = $result && count($result) ? $result[0] : null;
                $cache->set($cacheKey, $retVal);
            }
            return $retVal;
        }

        /**
         * Returns the current results and rounds of the bracket
         */
        public function getResults($force = false) {
            $cacheKey = 'Api:Bracket:getResults_' . $this->id;

            return Lib\Cache::getInstance()->fetchLongCache(function() {

                $retVal = [];

                // Calculate the number of tiers in the bracketbracket
                $row = Lib\Db::Fetch(
                    Lib\Db::Query('CALL proc_GetBracketRoundInfo(:bracketId)',
                    [ 'bracketId' => $this->id ]
                ));
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
                $retVal = Lib\Cache::getInstance()->fetchLongCache(function() use ($user) {
                    $params = [ 'userId' => $user->id, 'bracketId' => $this->id ];
                    $result = Lib\Db::Query('CALL proc_GetBracketVotesForUser(:bracketId, :userId)', $params);
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
            $this->_lock();

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

            $this->_unlock();
        }

        /**
         * Locks the bracket
         */
        private function _lock() {
            if (!$this->isLocked()) {
                $cache = Lib\Cache::getInstance();
                $cacheKey = $this->_lockedCacheKey();
                $cache->set($cacheKey, true, CACHE_MEDIUM);
            } else {
                throw new Error('This bracket is currently updating. Please check again in a few minutes.');
            }
        }

        /**
         * Unlocks the bracket
         */
        private function _unlock() {
            $cache = Lib\Cache::getInstance();
            $cacheKey = $this->_lockedCacheKey();
            $cache->set($cacheKey, false, CACHE_SHORT);
        }

        /**
         * Returns the locked status of the bracket
         */
        public function isLocked() {
            return Lib\Cache::getInstance()->get($this->_lockedCacheKey());
        }

        /**
         * Advances the elmination round to the next group or the bracket proper
         */
        private function _advanceEliminations() {
            // Get a list of rounds not yet completed
            $result = Lib\Db::Query('CALL proc_AdvanceEliminationRound(:bracketId)', [ ':bracketId' => $this->id ]);
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
                    $rounds[$i]->finalizeRound();
                    $rounds[$i + 1]->finalizeRound();

                }
            } else if (count($rounds) === 1) {
                $round = $rounds[0];
                $round->finalizeRound();
                $this->score = $this->getFinalScore();
                $this->winner = $round->getWinner();
                $this->winnerCharacterId = $this->winner->id;
                $this->state = BS_FINAL;
                $this->sync();
            } else {
                // Somehow, there are no more open rounds, so get the last one and use the winner from
                // that to close out the bracket
                $round = Round::queryReturnAll([ 'bracketId' => $this->id ], [ 'round_id' => 'desc' ], 1);
                if (count($round) === 1) {
                    // Get the total number of votes to use for the score
                    $round = $round[0];
                    $this->score = $this->getFinalScore();
                    $this->winner = $round->getWinner();
                    $this->winnerCharacterId = $this->winner->id;
                    $this->state = BS_FINAL;
                    $this->sync();
                }
            }

            // Clear the results cache
            Lib\Cache::getInstance()->set('Api:Bracket:getResults_' . $this->id, false, 1);
        }

        /**
         * Takes the results from the elimination rounds and creates a seeded bracket
         */
        public function createBracketFromEliminations($entrants, $groups) {

            $retVal = false;

            if (is_numeric($entrants)) {

                // Generate the bracket template
                $seeding = self::generateSeededBracket($entrants);

                $characters = $this->getVoteAdjustedEliminationsCharacters();
                if (count($characters) >= $entrants) {

                    // Set up the rounds
                    $groupSplit = $entrants / $groups;
                    for ($i = 0; $i < $entrants; $i += 2) {
                        $round = new Round();
                        $round->bracketId = $this->id;
                        $round->tier = 1;
                        $round->order = ($i + 1) % $groupSplit;
                        $round->group = floor($i / $groupSplit);
                        $round->final = 0;
                        $round->deleted = 0;

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
         * Returns an array of characters, their vote counts, and adjusted vote counts
         * for a bracket's elimination round
         */
        public function getVoteAdjustedEliminationsCharacters($maxDate = false) {
            $retVal = [];
            $maxDate = $maxDate ?: time();

            // Get the max vote counts for each day
            $params = [
                'bracketId' => $this->id,
                'maxDate' => $maxDate
            ];

            $result = Lib\Db::Query('CALL proc_GetEliminationVotesForGroups(:bracketId, :maxDate)', $params);
            $groupCounts = [];
            $max = 0;
            while ($row = Lib\Db::Fetch($result)) {
                $votes = (int) $row->total;
                $groupCounts[(int) $row->round_group] = $votes;
                $max = $votes > $max ? $votes : $max;
            }
            $result->comm->closeCursor();

            $params = [
                'bracketId' => $this->id,
                'maxDate' => $maxDate
            ];
            $result = Lib\Db::Query('CALL proc_GetEliminationGroupsCharacterVotes(:bracketId, :maxDate)', $params);

            // Ensure that we have characters and there are at least enough to meet the bracket constraints
            if ($result && $result->count) {
                while ($row = Lib\Db::Fetch($result)) {
                    $obj = new Character($row);
                    $obj->totalVotes = (int) $row->total;

                    // Normalize the votes against the highest day of voting to ensure that seeding order is reflective of flucuations in daily voting
                    $group = (int) $row->round_group;
                    $groupCount = $group < count($groupCounts) ? $groupCounts[$group] : null;
                    if ($groupCount !== null) {
                        if ($groupCount !== 0) {
                            $obj->adjustedVotes = round(($obj->totalVotes / $groupCount) * $max);
                        } else {
                            $obj->adjustedVotes = 0;
                        }
                    } else {
                        // If there's no group count for this group, no
                        // voting has actually happened
                        $obj->adjustedVotes = 'N/A';
                        $obj->totalVotes = 0;
                    }

                    $retVal[] = $obj;
                }

                // Reorder by adjusted votes
                usort($retVal, function($a, $b) {
                    // Having an expected mixed int/string is gross. But yam lazy
                    $aValue = $a->adjustedVotes !== 'N/A' ? $a->adjustedVotes : 0;
                    $bValue = $b->adjustedVotes !== 'N/A' ? $b->adjustedVotes : 0;
                    return $aValue < $bValue ? 1 : -1;
                });

                // Add a place value
                $place = 0;
                foreach($retVal as $character) {
                    $character->place = ++$place;
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
            $retVal = $entrants === 2 ? $buildFrom : [];
            if (empty($retVal)) {
              for ($i = 0, $count = count($buildFrom); $i < $count; $i++) {
                  $retVal[] = $buildFrom[$i];
                  $retVal[] = $count * 2 - ($buildFrom[$i] - 1);
              }
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
                $result = $this->createQuery()
                    ->select('id')
                    ->where('perma', $perma)
                    ->execute();
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
         * Rolls the bracket back to a previous state
         */
        public function rollback($tier, $group) {
            $retVal = false;

            $this->_lock();

            $this->state = $tier === 0 ? BS_ELIMINATIONS : BS_VOTING;

            $result = Lib\Db::Query('CALL proc_RollbackBracket(:bracketId, :tier, :group)', [
                'bracketId' => $this->id,
                'tier' => $tier,
                'group' => $group
            ]);

            $retVal = $result && $this->sync();

            $this->_unlock();

            return $retVal;
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
            return Lib\Db::Query(
                'CALL proc_AddUserToBracket(:bracketId, :userId)',
                [
                    ':bracketId' => $this->id,
                    ':userId' => $user->id
                ]
            );
        }

        /**
         * Removes a user's admin privileges from this bracket
         */
        public function removeUser(User $user) {
            return Lib\Db::Query(
                'CALL proc_RemoveUserFromBracket(:bracketId, :userId)',
                [
                    'bracketId' => $this->id,
                    'userId' => $user->id
                ]
            );
        }

        /**
         * Gets all users assigned to a bracket
         */
        public function getUsers() {
          $retVal = [];
          $result = Lib\Db::Query('CALL proc_GetBracketUsers(:bracketId)', [
            'bracketId' => $this->id
          ]);
          if ($result && $result->count) {
            while ($row = Lib\Db::Fetch($result)) {
              $retVal[] = new User($row);
            }
            $result->comm->closeCursor();
          }
          return $retVal;
        }

        /**
         * Returns the score for a finalized bracket
         */
        public function getFinalScore() {
            $retVal = 0;
            $result = Lib\Db::Query('CALL proc_GetBracketFinalScore(:bracketId)', [ 'bracketId' => $this->id ]);
            if ($result && $result->count) {
                $retVal = Lib\Db::Fetch($result);
                $retVal = $retVal->total;
            }
            return $retVal;
        }

        /**
         * Generator for the locked cache key
         */
        private function _lockedCacheKey() {
            return 'Api:Bracket:bracket_locked_' . $this->id;
        }

    }

}
