<?php

namespace Api {
	
	use Lib;
	
	class Round extends Dal {
	
		/**
		 * Object property to table column map
		 */
		protected $_dbMap = array(
			'roundId' => 'round_id',
			'bracketId' => 'bracket_id',
			'roundOrder' => 'round_order',
			'roundTier' => 'round_tier',
			'roundGroup' => 'round_group',
			'roundCharacter1Id' => 'round_character1_id',
			'roundCharacter2Id' => 'round_character2_id'
		);
		
		/**
		 * Database table
		 */
		protected $_dbTable = 'round';
		
		/**
		 * Primary key
		 */
		protected $_dbPrimaryKey = 'roundId';
	
		/**
		 * Round ID
		 */
		public $roundId = 0;
	
		/**
		 * Bracket ID
		 */
		public $bracketId = 0;
	
		/**
		 * Ordering for this round
		 */
		public $roundTier = 0;
	
		/**
		 * Ordering for this round
		 */
		public $roundOrder = 0;
	
		/**
		 * Ordering for this round
		 */
		public $roundGroup = 0;
	
		/**
		 * Character 1 Id
		 */
		public $roundCharacter1Id = 0;
		
		/**
		 * Character 1 object
		 */
		public $roundCharacter1;
		
		/**
		 * Character 2 object
		 */
		public $roundCharacter2;
		
		/**
		 * Character 2 Id
		 */
		public $roundCharacter2Id = 0;
		
		/**
		 * Constructor
		 */
		public function __construct($round = null) {
			if (is_object($round)) {
				$this->copyFromDbRow($round);
			}
		}
		
		/**
		 * Gets a character by ID
		 */
		public static function getRoundById($id) {
			$retVal = false;
			$params = array( ':roundId' => $id );
			$result = Lib\Db::Query('SELECT * FROM `round` WHERE `round_id` = :roundId', $params);
			if ($result && $result->count > 0) {
				$row = Lib\Db::Fetch($result);
				$retVal = new Round($row);
			}
			return $retVal;
		}
		
		/**
		 * Gets the unvoted rounds for a bracket and tier
		 */
		public static function getBracketRounds($bracketId, $tier, $group = false, $ignoreCache = false) {
			
			$ip = $_SERVER['REMOTE_ADDR'];
			$cacheKey = 'GetBracketRounds_' . $bracketId . '_' . $tier . '_' . ($group !== false ? $group : 'all') . '_' . $ip;
			$retVal = Lib\Cache::Get($cacheKey);
			if (false === $retVal || $ignoreCache) {
				$params = array( ':bracketId' => $bracketId, ':tier' => $tier, ':ip' => $ip, ':date' => strtotime('today') );
				
				if (false !== $group) {
					$params[':group'] = $group;
					
					// Check to see how many rounds there are in the group total. If there's only one, come back and get them all
					$row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(1) AS total FROM round WHERE bracket_id = :bracketId AND round_tier = :tier AND round_group = :group', array( ':bracketId' => $bracketId, ':tier' => $tier, ':group' => $group)));
					if ((int)$row->total == 1) {
						$retVal = self::getBracketRounds($bracketId, $tier, false, $ignoreCache);
						$result = null;
					} else {
						$result = Lib\Db::Query('SELECT * FROM round WHERE bracket_id = :bracketId AND round_tier = :tier AND round_group = :group AND round_id NOT IN (SELECT round_id FROM votes WHERE vote_ip = :ip AND vote_date >= :date) ORDER BY round_order', $params);
					}
				} else {
					$result = Lib\Db::Query('SELECT * FROM round WHERE bracket_id = :bracketId AND round_tier = :tier AND round_id NOT IN (SELECT round_id FROM votes WHERE vote_ip = :ip AND vote_date >= :date) ORDER BY round_order', $params);
				}
				
				if ($result && $result->count > 0) {
					$retVal = [];
					
					while ($row = Lib\Db::Fetch($result)) {
						$round = new Round($row);
						
						// If there tier is not 0, character2 is "nobody", and the number of items is not a power of two
						// this is a wildcard round and the user has already voted
						if ($row->round_tier != 0 && $row->round_character2_id == 1 && (($result->count + 1) & ($result->count)) != 0) {
							return null;
						}
						
						$round->roundCharacter1 = Character::getCharacterById($row->round_character1_id);
						$round->roundCharacter2 = Character::getCharacterById($row->round_character2_id);
						$retVal[] = $round;
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
					$round->roundCharacter1 = Character::getCharacterById($row->round_character1_id);
					$round->roundCharacter2 = Character::getCharacterById($row->round_character2_id);
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
					$round->roundCharacter1 = Character::getCharacterById($row->round_character1_id);
					$round->roundCharacter2 = Character::getCharacterById($row->round_character2_id);
					$retVal[] = $round;
				}
			}
			return $retVal;
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
	
	}

}