<?php

namespace Api {
	
	use Lib;
	
	class Nominee extends Dal {
		
		/**
		 * Property to database column map
		 */
		protected $_dbMap = array( 
			'_nomineeId' => 'nominee_id', 
			'_bracketId' => 'bracket_id',
			'_nomineeName' => 'nominee_name',
			'_nomineeSource' => 'nominee_source',
			'_nomineeCreated' => 'nominee_created',
			'_nomineeProcessed' => 'nominee_processed'
		);
		
		/**
		 * Database table
		 */
		protected $_dbTable = 'nominee';
		
		/**
		 * Primary key
		 */
		protected $_dbPrimaryKey = '_nomineeId';
		
		/**
		 * Record ID of the nominee
		 */
		protected $_nomineeId = 0;
		public function getNomineeId() { return $this->_nomineeId; }
		public function setNomineeId($value) { $this->_nomineeId = $value; }
	
		/**
		 * ID of the bracket this nominee belongs to
		 */
		protected $_bracketId;
		public function getBracketId() { return $this->_bracketId; }
		public function setBracketId($value) { $this->_bracketId = $value; }
		
		/**
		 * Name of nominee
		 */
		protected $_nomineeName;
		public function getNomineeName() { return $this->_nomineeName; }
		public function setNomineeName($value) { $this->_nomineeName = $value; }
		
		/**
		 * Nominee media source
		 */
		protected $_nomineeSource;
		public function getNomineeSource() { return $this->_nomineeSource; }
		public function setNomineeSource($value) { $this->_nomineeSource = $value; }
		
		/**
		 * Date the entry was created (unix time stamp)
		 */
		protected $_nomineeCreated;
		public function getNomineeCreated() { return $this->_nomineeCreated; }
		public function setNomineeCreated($value) { $this->_nomineeCreated = $value; }
		
		/**
		 * Date the entry was created (unix time stamp)
		 */
		protected $_nomineeProcessed;
		public function getNomineeProcessed() { return $this->_nomineeProcessed; }
		public function setNomineeProcessed($value) { $this->_nomineeProcessed = $value; }
		
		/**
		 * Constructor
		 */
		public function __construct($nominee = null) {
			if (is_object($nominee)) {
				$this->copyFromDbRow($nominee);
			}
		}
		
		/**
		 * 
		 */
		public static function getNomineeById($id) {
			$retVal = false;
			$params = array( ':nomineeId' => $id );
			$result = Lib\Db::Query('SELECT * FROM `nominee` WHERE `nominee_id` = :nomineeId', $params);
			if ($result && $result->count > 0) {
				$row = Lib\Db::Fetch($result);
				$retVal = new Nominee($row);
			}
			return $retVal;
		}
		
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
		
	}

}