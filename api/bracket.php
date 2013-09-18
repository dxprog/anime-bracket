<?php

namespace Api {
	
	use Lib;
	use stdClass;
	
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
			'rules' => 'bracket_rules'
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
		 * Override for getAll to include the winner character object
		 */
		public static function getAll() {
			$cacheKey = 'Api:Bracket:getAll';
			$retVal = Lib\Cache::Get($cacheKey);
			if (false === $retVal) {
				$retVal = parent::getAll();
				foreach ($retVal as $bracket) {
					if ($bracket->winnerCharacterId) {
						$bracket->winner = Character::getById($bracket->winnerCharacterId);
					}
				}
				Lib\Cache::Set($cacheKey, $retVal, 3600);
			}
			return $retVal;
		}

		/**
		 * Gets a bracket by perma lookup
		 */
		public static function getBracketByPerma($perma) {
			$cacheKey = 'Api:Bracket:getBracketByPerma_' . $perma;
			$retVal = Lib\Cache::Get($cacheKey);
			if (false === $retVal) {
				$result = Lib\Db::Query('SELECT * FROM `bracket` WHERE `bracket_perma` = :perma', [ ':perma' => $perma ]);
				if ($result && $result->count) {
					$retVal = new Bracket(Lib\Db::Fetch($result));
				}
				Lib\Cache::Set($cacheKey, $retVal);
			}
			return $retVal;
		}

		public function getResults() {
			$cacheKey = 'Api:Bracket:getResults_' . $this->id;
			$retVal = Lib\Cache::Get($cacheKey);
			if (false === $retVal) {

				$retVal = [];
				
				// Calculate the number of tiers in the bracket
				$row = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(DISTINCT round_tier) AS total FROM round WHERE bracket_id = :bracketId AND round_tier > 0', [ ':bracketId' => $this->id ]));
				$count = (int) $row->total + 1;

				for ($i = 1; $i < $count; $i++) {
					$rounds = Round::getRoundsByTier($this->id, $i);
					if ($rounds) {
						foreach ($rounds as $round) {
							$char1 = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(DISTINCT user_id) AS total FROM votes WHERE character_id = :id AND round_id = :round', array( ':id' => $round->character1Id, ':round' => $round->id)));
							$char2 = Lib\Db::Fetch(Lib\Db::Query('SELECT COUNT(DISTINCT user_id) AS total FROM votes WHERE character_id = :id AND round_id = :round', array( ':id' => $round->character2Id, ':round' => $round->id)));

							// Numericize where needed
							$round->id = (int) $round->id;
							$round->order = (int) $round->order;
							$round->group = (int) $round->group;
							$round->tier = (int) $round->tier;
							$round->character1->id = (int) $round->character1->id;
							$round->character2->id = (int) $round->character2->id;
							$round->character1->votes = (int) $char1->total;
							$round->character2->votes = (int) $char2->total;

							// Toss out some extraneous data
							unset($round->bracketId);
							unset($round->character1Id);
							unset($round->character2Id);
							unset($round->character1->bracketId);
							unset($round->character2->bracketId);
							unset($round->voted);

						}
					}
					$retVal[] = $rounds;
				}

				// Do a super long cache for finalized brackets
				$cacheLength = $this->state = BS_FINAL ? 84600 : 3600;
				Lib\Cache::Set($cacheKey, $retVal);

			}

			return $retVal;

		}

		public function getVotesForUser($user) {
			$retVal = null;
			if ($user instanceof User) {
				$cacheKey = 'Api:Bracket:getVotesForUser_' . $user->id;
				$retVal = Lib\Cache::Get($cacheKey);
				if (false === $retVal) {
					$params = [ ':userId' => $user->id, ':bracketId' => $this->id ];
					$result = Lib\Db::Query('SELECT v.round_id, v.character_id FROM votes v INNER JOIN `round` r ON r.round_id = v.round_id WHERE v.user_id = :userId AND r.bracket_id = :bracketId', $params);
					$retVal = [];
					if ($result && $result->count) {
						while ($row = Lib\Db::Fetch($result)) {
							$retVal[$row->round_id] = (int) $row->character_id;
						}
					}
					Lib\Cache::Set($cacheKey, $retVal);
				}
			}
			return $retVal;
		}
	
	}

}