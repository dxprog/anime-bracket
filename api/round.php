<?php

namespace Api {
	
	use Lib;
	
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
			'character2Id' => 'round_character2_id'
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
		 * Character 2 object
		 */
		public $character2;
		
		/**
		 * Character 2 Id
		 */
		public $character2Id;
		
		/**
		 * Whether the user has voted on this round
		 */
		public $voted = false;
		
		/**
		 * Constructor
		 */
		public function __construct($round = null) {
			if (is_object($round)) {
				parent::copyFromDbRow($round);
				if (isset($round->user_vote)) {
					$this->voted = $round->user_vote > 0;
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
					if ((int)$row->total == 1) {
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
					
					while ($row = Lib\Db::Fetch($result)) {
						$round = new Round($row);
						
						// If there tier is not 0, character2 is "nobody", and the number of items is not a power of two
						// this is a wildcard round and the user has already voted
						if ($row->round_tier != 0 && $row->round_character2_id == 1 && (($result->count + 1) & ($result->count)) != 0) {
							return null;
						}
						
						$round->character1 = Character::getById($row->round_character1_id);
						$round->character2 = Character::getById($row->round_character2_id);
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