<?php

namespace Api {
	
	use Lib;
	
	class Character extends Dal {
	
		/**
		 * Object property to table column map
		 */
		protected $_dbMap = array(
			'characterId' => 'character_id',
			'bracketId' => 'bracket_id',
			'characterName' => 'character_name',
			'characterSource' => 'character_source'
		);
		
		/**
		 * Database table
		 */
		protected $_dbTable = 'character';
		
		/**
		 * Primary key
		 */
		protected $_dbPrimaryKey = 'characterId';
	
		/**
		 * Character record ID
		 */
		public $characterId = 0;
	
		/**
		 * Bracket ID
		 */
		public $bracketId = 0;
	
		/**
		 * Character name
		 */
		public $characterName = 0;
	
		/**
		 * Character source
		 */
		public $characterSource = 0;
		
		/**
		 * Image path for this character
		 */
		public $characterImage = '';
		
		/**
		 * Constructor
		 */
		public function __construct($character = null) {
			if (is_object($character)) {
				$this->copyFromDbRow($character);
				$this->characterImage = base_convert($this->characterId, 10, 36) . '.jpg';
			}
		}
		
		/**
		 * Gets a character by ID
		 */
		public static function getCharacterById($id) {
			$retVal = false;
			$params = array( 'characterId' => $id );
			$result = Lib\Db::Query('SELECT * FROM `character` WHERE `character_id` = :characterId', $params);
			if ($result && $result->count > 0) {
				$row = Lib\Db::Fetch($result);
				$retVal = new Character($row);
			}
			return $retVal;
		}
	
	}

}