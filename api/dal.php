<?php

namespace Api {
	
	use Lib;
	
	class Dal {
		
		/**
		 * Syncs the current object to the database
		 */
		public function sync() {
			
			$retVal = 0;
			
			if (property_exists($this, '_dbTable') && property_exists($this, '_dbMap')) {
				
				$dbParams = array();
				
				// Determine if a primary key was set
				$primaryKey = property_exists($this, '_dbPrimaryKey') ? $this->_dbPrimaryKey : false;
				$primaryKeyValue = 0;
				if ($primaryKey) {
					$primaryKeyValue = $this->$primaryKey;
				}
				
				// If the primary key value is non-zero, do an UPDATE
				$method = $primaryKeyValue !== 0 ? 'UPDATE' : 'INSERT';
				$parameters = array();
				
				foreach ($this->_dbMap as $property => $column) {
					// Primary only gets dropped in for UPDATEs
					if (($primaryKey === $property && 'UPDATE' === $method) || $primaryKey !== $property) {
						$paramName = ':' . $property;
						$params[$paramName] = $this->$property;
						if ('INSERT' === $method) {
							$parameters[] = $paramName;
						} else if ($primaryKey != $property) {
							$parameters[] = '`' . $column . '` = ' . $paramName;
						}
					}
				}
				
				// Build and execute the query
				$query = $method;
				if ('INSERT' === $method) {
					$query .= ' INTO `' . $this->_dbTable . '` (`' . implode('`,`', $this->_dbMap) . '`) VALUES (' . implode(',', $parameters) . ')';
					$query = str_replace('`' . $this->_dbMap[$primaryKey] . '`,', '', $query);
				} else {
					$query .= ' `' . $this->_dbTable . '` SET ' . implode(',', $parameters) . ' WHERE `' . $this->_dbMap[$primaryKey] . '` = :' . $primaryKey;
				}
				
				$retVal = Lib\Db::Query($query, $params);
				
				// Save the ID for insert
				if ($retVal > 0 && 'INSERT' === $method) {
					$this->$primaryKey = $retVal;
				}
				
			}
			
			return $retVal > 0;
		
		}
		
		/**
		 * Creates an object from the passed database row
		 */
		public function copyFromDbRow($obj) {
			if (property_exists($this, '_dbMap') && is_object($obj)) {
				foreach($this->_dbMap as $property => $column) {
					if (property_exists($obj, $column) && property_exists($this, $property)) {
						$this->$property = $obj->$column;
					}
				}
			}
		}
	
	}

}