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

		public function getVotesForUser(User $user) {
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

		/**
		 * Advances the bracket eliminations
		 */
		public function advance() {

			switch ($this->state) {
				case BS_ELIMINATIONS:
					$this->_advanceEliminations();
					break;
			}

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
		 * Takes the results from the elimination rounds and creates a seeded bracket
		 */
		public function createBracketFromEliminations($entrants, $groups) {

			if (is_numeric($entrants)) {
				
				// Generate the bracket template
				$seeding = self::generateSeededBracket($entrants);
				
				// Get the entrants sorted by their votes in descending order
				$characters = [];
				$result = Lib\Db::Query('SELECT COUNT(1) AS total, v.character_id FROM votes v INNER JOIN round r ON r.round_id = v.round_id WHERE r.bracket_id = :bracketId GROUP BY v.character_id ORDER BY total DESC LIMIT ' . $entrants, [ ':bracketId' => $this->id ]);
				while ($row = Lib\Db::Fetch($result)) {
					$characters[] = (int) $row->character_id;
				}

				// Set up the rounds
				$groupSplit = $entrants / $groups;
				for ($i = 0; $i < $entrants; $i += 2) {
					$round = new Round();
					$round->bracketId = $this->id;
					$round->tier = 1;
					$round->order = ($i + 1) % $groupSplit;
					$round->group = floor($i / $groupSplit);
					$round->character1Id = $characters[$seeding[$i] - 1];
					$round->character2Id = $characters[$seeding[$i + 1] - 1];
					$round->sync();
				}

			}

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
	
	}

}