<?php

namespace Api {

    use Lib;
    use stdClass;

    class Character extends Lib\Dal {

        /**
         * Object property to table column map
         */
        protected $_dbMap = array(
            'id' => 'character_id',
            'bracketId' => 'bracket_id',
            'name' => 'character_name',
            'source' => 'character_source',
            'seed' => 'character_seed',
            'meta' => 'character_meta'
        );

        /**
         * Database table
         */
        protected $_dbTable = 'character';

        /**
         * Primary key
         */
        protected $_dbPrimaryKey = 'id';

        /**
         * Character record ID
         */
        public $id = 0;

        /**
         * Bracket ID
         */
        public $bracketId = 0;

        /**
         * Character name
         */
        public $name = 0;

        /**
         * Character source
         */
        public $source = 0;

        /**
         * Image path for this character
         */
        public $image = '';

        /**
         * Character's seed (if they are in the bracket voting)
         */
        public $seed;

        /**
         * Assorted metadata, JSON encoded to the database
         */
        public $meta;

        /**
         * Override for image
         */
        public function copyFromDbRow($row) {
            parent::copyFromDbRow($row);
            $this->id = (int) $this->id;
            $this->bracketId = (int) $this->bracketId;
            $this->seed = (int) $this->seed;
            $this->image = IMAGE_URL . '/' . base_convert($this->id, 10, 36) . '.jpg';
            if ($this->meta) {
                $this->meta = json_decode($this->meta);
            }
        }

        /**
         * Override to ensure meta is saved as JSON
         */
        public function sync($forceInsert = false) {
            $oldMeta = $this->meta;
            $this->meta = json_encode($this->meta);
            $retVal = parent::sync($forceInsert);
            $this->meta = $oldMeta;
            return $retVal;
        }

        /**
         * Gets all characters for a bracket
         */
        public static function getByBracketId($bracketId) {
            $retVal = null;
            if (is_numeric($bracketId)) {
                $retVal = Lib\Cache::getInstance()->fetch(function() use ($bracketId) {
                    return Character::queryReturnAll([ 'bracketId' => $bracketId ], [ 'name' => 'ASC' ]);
                }, 'Character_getByBracketId_' . $bracketId);
            }
            return $retVal;
        }

        /**
         * Gets other characters in this bracket with similar names (checks for Japanese and Western naming order)
         */
        public static function getBySimilarName($name) {
            $retVal = null;

            $query = 'SELECT * FROM `character` WHERE ';
            $params = [];
            $name = explode(' ', $name);
            if (count($name) === 2) {
                $params[':nameA'] = $name[0] . '%' . $name[1];
                $params[':nameB'] = $name[1] . '%' . $name[0];
                $query .= '(character_name LIKE :nameA OR character_name LIKE :nameB)';
            } else {
                $params[':name'] = implode('%', $name);
                $query .= 'character_name LIKE :name';
            }

            $result = Lib\Db::Query($query, $params);

            if ($result && $result->count) {
                $retVal = [];
                while ($row = Lib\Db::Fetch($result)) {
                    $obj = new Character($row);
                    $obj->bracket = Bracket::getById($obj->bracketId);
                    $retVal[] = $obj;
                }
            }

            return $retVal;

        }

        /**
         * Does fuzzy searching for characters within a bracket
         */
        public static function searchBracketCharacters($query, $bracketId) {
            return Lib\Cache::getInstance()->fetch(function() use ($query, $bracketId) {

                $retVal = [];
                $params = [
                    ':bracketId' => $bracketId,

                    // essentially a first name search
                    ':queryA' => $query . '%',

                     // last name search
                    ':queryB' => '% ' . $query . '%'
                ];

                $result = Lib\Db::Query('SELECT * FROM `character` WHERE bracket_id = :bracketId AND (character_name LIKE :queryA OR character_name LIKE :queryB)', $params);
                if ($result && $result->count) {
                    while ($row = Lib\Db::Fetch($result)) {
                        $retVal[] = new Character($row);
                    }
                }

                return $retVal;

            }, 'Api::Character_searchCharacters_' . $query . '_' . $bracketId, CACHE_MEDIUM);
        }

        /**
         * Returns a random number of characters from bracket
         */
        public static function getRandomCharacters(Bracket $bracket, $count) {

            if (is_numeric($count)) {
                return Lib\Cache::getInstance()->fetch(function() use ($bracket, $count) {
                    $retVal = [];
                    $result = Lib\Db::Query('SELECT * FROM `character` WHERE bracket_id = :id ORDER BY RAND() LIMIT ' . $count, [ ':id' => $bracket->id ]);
                    if ($result && $result->count) {
                        while ($row = Lib\Db::Fetch($result)) {
                            $retVal[] = new Character($row);
                        }
                    }
                    return $retVal;
                }, 'Api::Character_getRandomCharacters_' . $bracket->id . '_' . $count);
            }

            return [];

        }

        /**
         * Given the passed character, returns the characters that users also voted for
         * @param $sameSource bool Return only characters that are from the same source
         * @param $excludeEliminations bool Ignore votes cast in the elimination round
         * @return array Character objects and the percentage of users that also voted for them
         */
        public function getCharactersAlsoVotedFor($sameSource = false, $excludeEliminations = false, $limit = 5) {

            $voterCount = $this->getVoterCount();
            $retVal = null;

            if ($voterCount > 0) {

                $params = [ ':bracketId' => $this->bracketId, ':characterId' => $this->id ];

                $query = 'SELECT COUNT(DISTINCT v.user_id) AS total, c.* FROM votes v ';
                $query .= 'INNER JOIN `character` c ON c.character_id = v.character_id ';
                $query .= 'INNER JOIN `round` r ON r.round_id = v.round_id ';

                $query .= 'WHERE v.bracket_id = :bracketId ';
                $query .= 'AND v.character_id != :characterId ';
                $query .= 'AND r.round_final = 1 ';
                $query .= 'AND v.user_id IN (SELECT DISTINCT user_id FROM votes WHERE character_id = :characterId) ';

                if ($sameSource) {
                    $query .= 'AND v.character_id IN (SELECT character_id FROM `character` WHERE character_source = :source) ';
                    $params[':source'] = $this->source;
                }

                if ($excludeEliminations) {
                    $query .= 'AND r.round_tier > 0 ';
                }

                $query .= 'GROUP BY v.character_id ORDER BY total DESC';

                if (is_numeric($limit)) {
                    $query .= ' LIMIT ' . $limit;
                }

                $result = Lib\Db::Query($query, $params);
                if ($result && $result->count > 0) {

                    $retVal = [];
                    while ($row = Lib\Db::Fetch($result)) {
                        $obj = new stdClass;
                        $obj->character = new Character($row);
                        $obj->percent = round($row->total / $voterCount * 100);
                        $retVal[] = $obj;
                    }

                }

            }

            return $retVal;

        }

        /**
         * Returns the average percentage of votes a character receives each round
         */
        public function getAverageRoundPerformance($excludeEliminations = false) {
            $retVal = null;

            if ($this->bracketId && $this->id) {
                $query = 'SELECT COUNT(1) AS total, ' .
                         'SUM(CASE WHEN v.character_id = :characterId THEN 1 ELSE 0 END) AS character_votes ' .
                         'FROM votes v INNER JOIN round r ON r.round_id = v.round_id ' .
                         'WHERE v.bracket_id = :bracketId ' .
                         ($excludeEliminations ? 'AND r.round_tier > 0 ' : '') .
                         'AND v.round_id IN (SELECT round_id FROM round WHERE (round_character1_id = :characterId ' .
                         'OR round_character2_id = :characterId)' .
                         'AND round_final = 1)';
                $result = Lib\Db::Query($query, [ ':characterId' => $this->id, ':bracketId' => $this->bracketId ]);

                if ($result && $result->count === 1) {
                    $row = Lib\Db::Fetch($result);
                    $retVal = (int) $row->character_votes / (int) $row->total;
                }
            }

            return $retVal;
        }

        /**
         * Gets the number of unique users who has voted for this character
         */
        public function getVoterCount() {
            $retVal = null;

            if ($this->id > 0 AND $this->bracketId > 0) {
                $retVal = 0;
                $result = Lib\Db::Query('SELECT COUNT(DISTINCT user_id) AS total FROM votes WHERE character_id = :characterId', [ ':characterId' => $this->id ]);
                if ($result && $result->count === 1) {
                    $row = Lib\Db::Fetch($result);
                    $retVal = (int) $row->total;
                }
            }

            return $retVal;
        }

    }

}
