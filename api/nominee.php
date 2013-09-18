<?php

namespace Api {
	
	use Lib;
	
	class Nominee extends Lib\Dal {
		
		/**
		 * Property to database column map
		 */
		protected $_dbMap = array( 
			'id' => 'nominee_id', 
			'bracketId' => 'bracket_id',
			'name' => 'nominee_name',
			'source' => 'nominee_source',
			'created' => 'nominee_created',
			'processed' => 'nominee_processed',
			'image' => 'nominee_image'
		);
		
		/**
		 * Database table
		 */
		protected $_dbTable = 'nominee';
		
		/**
		 * Primary key
		 */
		protected $_dbPrimaryKey = 'id';
		
		/**
		 * Record ID of the nominee
		 */
		public $id = 0;
	
		/**
		 * ID of the bracket this nominee belongs to
		 */
		public $bracketId;
		
		/**
		 * Name of nominee
		 */
		public $name;
		
		/**
		 * Nominee media source
		 */
		public $source;
		
		/**
		 * Date the entry was created (unix time stamp)
		 */
		public $created;
		
		/**
		 * Date the entry was created (unix time stamp)
		 */
		public $processed;
		
		/**
		 * Link to nominee image
		 */
		public $image;

		
		/**
		 * Returns all unprocessed 
		 */
		public static function getUnprocessed($bracketId, $count = 0) {
			$retVal = false;
			if (is_numeric($bracketId) && $bracketId > 0) {
				$params = array( 'bracketId' => $bracketId, 'processed' => 0 );
				$limit = is_numeric($count) && $count > 0 ? ' LIMIT ' . $count : '';
				$result = Lib\Db::Query('SELECT * FROM nominee WHERE bracket_id = :bracketId AND (nominee_processed IS NULL OR nominee_processed = :processed) ORDER BY RAND()' . $limit, $params);
				$retVal = array();
				while ($row = Lib\Db::Fetch($result)) {
					$retVal[] = new Nominee($row);
				}
			}
			return $retVal;
		}

		/**
		 * Gets other nominees in this bracket with similar names (checks for Japanese and Western naming order)
		 */
		public function getSimilar($bracket) {
			$retVal = null;
			if ($bracket instanceof Bracket) {
				$query = 'SELECT * FROM `nominee` WHERE bracket_id = :bracketId AND nominee_id != :nomineeId AND nominee_processed IS NULL ';
				$params = [ 'bracketId' => $bracket->id, ':nomineeId' => $this->id ];
				$name = explode(' ', trim($this->name));
				if (count($name) === 2) {
					$params[':nameA'] = $name[0] . '%' . $name[1];
					$params[':nameB'] = $name[1] . '%' . $name[0];
					$query .= 'AND (nominee_name LIKE :nameA OR nominee_name LIKE :nameB)';
				} else {
					$params[':name'] = implode('%', $name);
					$query .= 'AND nominee_name LIKE :name';
				}

				$result = Lib\Db::Query($query, $params);
				if ($result && $result->count) {
					$retVal = [];
					while ($row = Lib\Db::Fetch($result)) {
						$retVal[] = new Nominee($row);
					}
				}
			}
			return $retVal;
		}
		
	}

}