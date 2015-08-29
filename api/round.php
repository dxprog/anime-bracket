<?php

namespace Api {

    use Lib;
    use stdClass;

    class Round extends Lib\Dal {

        /**
         * Object property to table column map
         */
        protected $_dbMap = array(
            'id' => 'round_id',
            'bracketId' => 'bracket_id',
            'order' => 'round_order',
            'tier' => 'round_tier',
            'group' => 'round_group',
            'character1Id' => 'round_character1_id',
            'character1Votes' => 'round_character1_votes',
            'character2Id' => 'round_character2_id',
            'character2Votes' => 'round_character2_votes',
            'final' => 'round_final'
        );

        /**
         * Database table
         */
        protected $_dbTable = 'round';

        /**
         * Primary key
         */
        protected $_dbPrimaryKey = 'id';

        /**
         * Round ID
         */
        public $id = 0;

        /**
         * Bracket ID
         */
        public $bracketId;

        /**
         * Ordering for this round
         */
        public $tier;

        /**
         * Ordering for this round
         */
        public $order;

        /**
         * Ordering for this round
         */
        public $group;

        /**
         * Character 1 Id
         */
        public $character1Id;

        /**
         * Character 1 object
         */
        public $character1;

        /**
         * Number of votes that character 1 received (after round is final)
         */
        public $character1Votes;

        /**
         * Character 2 object
         */
        public $character2;

        /**
         * Character 2 Id
         */
        public $character2Id;

        /**
         * Number of votes that character 2 received (after round is final)
         */
        public $character2Votes;

        /**
         * Whether the user has voted on this round
         */
        public $voted = false;

        /**
         * ID of the character the user voted for
         */
        public $votedCharacterId;

        /**
         * Whether the round voting has been finalized
         */
        public $final = false;

        /**
         * Constructor
         */
        public function __construct($round = null) {
            if (is_object($round)) {
                parent::copyFromDbRow($round);
                $this->final = (bool) ord($this->final); // Because PHP is retarded about return BIT types from MySQL
                if (isset($round->user_vote)) {
                    $this->voted = $round->user_vote > 0;
                    $this->votedCharacterId = (int) $round->user_vote;
                }
            }
        }

        /**
         * Gets the unvoted rounds for a bracket and tier
         */
        public static function getBracketRounds($bracketId, $tier, $group = false, $ignoreCache = false) {

            // If no user, check as guest
            $user = User::getCurrentUser();
            if (!$user) {
                $user = new User;
                $user->id = 0;
            }

            $cacheKey = 'GetBracketRounds_' . $bracketId . '_' . $tier . '_' . ($group !== false ? $group : 'all') . '_' . $user->id;
            $retVal = Lib\Cache::Get($cacheKey);
            if (false === $retVal || $ignoreCache) {
                $params = [ ':bracketId' => $bracketId, ':tier' => $tier, ':userId' => $user->id ];

                if (false !== $group) {
                    $params[':group'] = $group;

                    // Check to see how many rounds there are in the group total. If there's only one, come back and get them all
                    $row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM round WHERE bracket_id = :bracketId AND round_tier = :tier AND round_group = :group', [ ':bracketId' => $bracketId, ':tier' => $tier, ':group' => $group ]));
                    if (is_object($row) && (int)$row->total == 1) {
                        $retVal = self::getBracketRounds($bracketId, $tier, false, $ignoreCache);
                        $result = null;
                    } else {
                        $result = Lib\Db::Query('SELECT *, (SELECT character_id FROM votes WHERE user_id = :userId AND round_id = r.round_id) AS user_vote FROM round r WHERE r.bracket_id = :bracketId AND r.round_tier = :tier AND r.round_group = :group ORDER BY r.round_order', $params);
                    }
                } else {
                    $result = Lib\Db::Query('SELECT *, (SELECT character_id FROM votes WHERE user_id = :userId AND round_id = r.round_id) AS user_vote FROM round r WHERE r.bracket_id = :bracketId AND r.round_tier = :tier ORDER BY r.round_order', $params);
                }

                if ($result && $result->count > 0) {
                    $retVal = [];

                    // Hashmap of characters to retrieve in the next step
                    $characters = [];

                    while ($row = Lib\Db::Fetch($result)) {
                        $round = new Round($row);

                        // If the tier is not 0, character2 is "nobody", and the number of items is not a power of two
                        // this is a wildcard round and the user has already voted
                        if ($row->round_tier != 0 && $row->round_character2_id == 1 && (($result->count + 1) & ($result->count)) != 0) {
                            return null;
                        }

                        // Save off the character IDs for retrieval later
                        $characters[$row->round_character1_id] = true;
                        $characters[$row->round_character2_id] = true;

                        $retVal[] = $round;
                    }

                    // Retrieve the characters
                    $result = Character::query([ 'id' => [ 'in' => array_keys($characters) ] ]);
                    if ($result && $result->count) {
                        while ($row = Lib\Db::Fetch($result)) {
                            $character = new Character($row);
                            $characters[$character->id] = $character;
                        }

                        // Replace all the instances for the rounds
                        foreach ($retVal as $round) {
                            $round->character1 = $characters[$round->character1Id];
                            $round->character2 = $characters[$round->character2Id];

                            // Flag the character the user voted for if the voted
                            if ($round->votedCharacterId) {
                                if ($round->votedCharacterId == $round->character1->id) {
                                    $round->character1->voted = true;
                                } else {
                                    $round->character2->voted = true;
                                }
                            }

                        }

                    }

                }
                Lib\Cache::Set($cacheKey, $retVal);
            }

            return $retVal;

        }

        public static function getRoundsByTier($bracketId, $tier) {
            $retVal = null;
            $params = array( ':bracketId' => $bracketId, ':tier' => $tier );
            $result = Lib\Db::Query('SELECT * FROM round WHERE bracket_id = :bracketId AND round_tier = :tier ORDER BY round_tier, round_group, round_order', $params);
            if ($result && $result->count > 0) {
                $retVal = [];
                while ($row = Lib\Db::Fetch($result)) {
                    $round = new Round($row);
                    $round->character1 = Character::getById($row->round_character1_id);
                    $round->character2 = Character::getById($row->round_character2_id);
                    $retVal[] = $round;
                }
            }
            return $retVal;
        }

        public static function getRoundsByGroup($bracketId, $tier, $group) {
            $retVal = null;
            $params = array( ':bracketId' => $bracketId, ':tier' => $tier, ':group' => $group );
            $result = Lib\Db::Query('SELECT * FROM round WHERE bracket_id = :bracketId AND round_tier = :tier AND round_group = :group ORDER BY round_tier, round_group, round_order', $params);
            if ($result && $result->count > 0) {
                $retVal = [];
                while ($row = Lib\Db::Fetch($result)) {
                    $round = new Round($row);
                    $round->character1 = Character::getById($row->round_character1_id);
                    $round->character2 = Character::getById($row->round_character2_id);
                    $retVal[] = $round;
                }
            }
            return $retVal;
        }

        /**
         * Returns the number of rounds for a given tier
         */
        public static function getRoundCountForTier(Bracket $bracket, $tier) {
            return Lib\Cache::fetch(function() use ($bracket, $tier) {
                $row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM round WHERE round_tier = :tier AND bracket_id = :bracketId', [ ':tier' => $tier, ':bracketId' => $bracket->id ]));
                return (int) $row->total;
            }, 'Api:Round:getRoundCountForTier_' . $bracket->id . '_' . $tier);
        }

        /**
         * Gets the highest tier set up in the bracket
         */
        public static function getCurrentRounds($bracketId, $ignoreCache = false) {

            $retVal = false;

            $params = array( ':bracketId' => $bracketId );
            $result = Lib\Db::Query('SELECT MIN(round_tier) AS tier FROM `round` WHERE bracket_id = :bracketId AND round_final = 0', $params);
            if ($result && $result->count > 0) {
                $row = Lib\Db::Fetch($result);
                $params[':tier'] = $row->tier;
                $result = Lib\Db::Query('SELECT MIN(round_group) AS `group` FROM `round` WHERE bracket_id = :bracketId AND round_tier = :tier AND round_final = 0', $params);
                if ($result && $result->count > 0) {
                    $row = Lib\Db::Fetch($result);
                    $retVal = self::getBracketRounds($bracketId, $params[':tier'], $row->group, $ignoreCache);
                }
            }

            return $retVal;

        }

        /**
         * Gets the rounds to come after the active round
         */
        public static function getNextRounds(Bracket $bracket) {
            $retVal = null;
            if ($bracket->state == BS_ELIMINATIONS || $bracket->state == BS_VOTING) {
                $result = Lib\Db::Query('CALL proc_GetNextRounds(:bracketId)', [ ':bracketId' => $bracket->id ]);
                if ($result && $result->count) {
                    $row = Lib\Db::Fetch($result);
                    $retVal = self::getBracketRounds($bracket->id, $row->nextTier, $row->nextGroup);
                }
            }
            return $retVal;
        }

        /**
         * Returns the character ID for the winner of the current round
         */
        public function getWinnerId() {
            $retVal = null;
            $result = Lib\Db::Query('CALL proc_GetRoundWinner(:roundId)', [ ':roundId' => $this->id ]);
            if ($result && $result->count) {
                $row = Lib\Db::Fetch($result);
                $retVal = $row->character_id;
            }
            return $retVal;
        }

        /**
         * Returns the character object for the winner of the current round
         */
        public function getWinner() {
            return Character::getById($this->getWinnerId());
        }

        public function getVoteCount() {
            $retVal = 0;
            $result = Lib\Db::Query('SELECT COUNT(1) AS total, character_id FROM votes WHERE round_id = :id GROUP BY character_id', [ ':id' => $this->id ]);
            if ($result && $result->count) {
                while ($row = Lib\Db::Fetch($result)) {
                    if ($row->character_id == $this->character1Id) {
                        $this->character1Votes = (int) $row->total;
                    } else {
                        $this->character2Votes = (int) $row->total;
                    }
                    $retVal += (int) $row->total;
                }

            }
            return $retVal;
        }

        public static function getVotingStats($bracketId) {
            $retVal = null;
            if (is_numeric($bracketId)) {
                $retVal = Lib\Cache::fetch(function() use ($bracketId) {
                    $retVal = null;
                    $result = Lib\Db::Query('SELECT COUNT(1) AS total, COUNT(DISTINCT v.user_id) AS user_total, r.round_tier, r.round_group FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE v.bracket_id = :bracketId GROUP BY r.round_tier, r.round_group', [ ':bracketId' => $bracketId ]);
                    if ($result && $result->count) {
                        $retVal = [];
                        while ($row = Lib\Db::Fetch($result)) {
                            $obj = new stdClass;
                            $obj->total = (int) $row->total;
                            $obj->userTotal = (int) $row->user_total;
                            $obj->tier = (int) $row->round_tier;
                            $obj->group = (int) $row->round_group;
                            $retVal[] = $obj;
                        }
                    }
                    return $retVal;
                }, 'Api:Round:getVotingStates_' . $bracketId);
            }
            return $retVal;
        }

        /**
         * Returns random completed rounds
         */
        public static function getRandomCompletedRounds($count) {
            $count = is_numeric($count) ? $count : 10;

            return Lib\Cache::fetch(function() use ($count) {

                $retVal = null;

                $query = 'SELECT * FROM `round` WHERE round_final = 1 AND round_tier > 0 AND round_character2_id > 1 ORDER BY RAND() LIMIT ' . $count;
                return self::_getRoundsAndCharacters($query);

            }, 'Round::getRandomCompletedRounds_' . $count, CACHE_LONG * 24);
        }

        /**
         * Returns the name of the active round for a bracket
         */
        public static function getBracketTitleForActiveRound(Bracket $bracket) {

            $retVal = '';
            if ($bracket->state == BS_NOMINATIONS) {
                $retVal = 'Accepting Nominations';
            } else if ($bracket->state == BS_FINAL) {
                $retVal = 'Final';
            } else if ($bracket->state == BS_NOT_STARTED) {
                $retVal = 'Not started';
            } else {
                $rounds = self::getCurrentRounds($bracket->id);
                if ($rounds) {
                    $retVal = self::getBracketTitleForRound($bracket, $rounds[0]);
                }
            }

            return $retVal;

        }

        /**
         * Returns the name for the provided rounds
         */
        public static function getBracketTitleForRound(Bracket $bracket, Round $round) {

            $retVal = '';

            // Get all other rounds (if none were provided) in this tier to determine special titles
            $roundsInTier = self::getRoundsByTier($bracket->id, $round->tier);
            $roundCount = count($roundsInTier);
            if ($bracket->state == BS_VOTING && $roundCount <= 4) {
                switch ($roundCount) {
                    case 4:
                        $retVal = 'Quarter Finals';
                        break;
                    case 2:
                        $retVal = 'Semi Finals';
                        break;
                    case 1:
                        $retVal = 'Title Match';
                        break;
                }
            }

            // If no special title was generated, generate based on the group
            if (!$retVal) {
                $retVal = $bracket->state == BS_ELIMINATIONS ? 'Eliminations - ' : 'Voting - Round ' . $round->tier . ', ';

                $group = 'All Groups';
                $groups = [];
                foreach ($roundsInTier as $tierRound) {
                    if (isset($groups[$tierRound->group])) {
                        $group = 'Group ' . chr($round->group + 65);
                        break;
                    }
                    $groups[$tierRound->group] = true;
                }

                $retVal .= $group;
            }

            return $retVal;

        }

        /**
         * Gets a full dataset including characters for multiple rounds
         */
        private static function _getRoundsAndCharacters($query, $params = null) {

            $retVal = null;
            $result = Lib\Db::Query($query, $params);
            if ($result && $result->count) {

                // This array will hold all unique character IDs (and later character objects)
                // to retrieve so we reduce the number of trips to the database.
                $characters = [];
                $retVal = [];

                while ($row = Lib\Db::Fetch($result)) {
                    $round = new Round($row);
                    $characters[$round->character1Id] = true;
                    $characters[$round->character2Id] = true;
                    $retVal[] = new Round($row);
                }

                // Now fetch the character objects
                $result = Character::query([ 'id' => [ 'in' => array_keys($characters) ] ]);
                if ($result && $result->count) {
                    while ($row = Lib\Db::Fetch($result)) {
                        $character = new Character($row);
                        $characters[$character->id] = $character;
                    }

                    // Now, assign the character objects to their rounds
                    for ($i = 0, $count = count($retVal); $i < $count; $i++) {
                        $retVal[$i]->character1 = $characters[$retVal[$i]->character1Id];
                        $retVal[$i]->character2 = $characters[$retVal[$i]->character2Id];
                    }

                }

            }

            return $retVal;

        }

    }

}