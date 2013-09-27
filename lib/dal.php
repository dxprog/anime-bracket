<?php

namespace Lib {
	
    use Api;
    use Exception;
    use stdClass;

	class Dal {
		
        /**
         * Constructor
         */
        public function __construct($obj = null) {
        
            if (is_numeric($obj)) {
                $this->getById($obj);
            } else if (is_object($obj)) {
                $this->copyFromDbRow($obj);
            }
        
        }
        
		/**
		 * Syncs the current object to the database
		 */
		public function sync() {
			
			$retVal = false;

			if (property_exists($this, '_dbTable') && property_exists($this, '_dbMap')) {
				
				$dbParams = array();
				
				// Determine if a primary key was set
				$primaryKey = property_exists($this, '_dbPrimaryKey') ? $this->_dbPrimaryKey : false;
				$primaryKeyValue = 0;
				if ($primaryKey) {
					$primaryKeyValue = (int) $this->$primaryKey;
				}

				// Get a copy of the object out of the database for comparison later
				$original = null;
				$className = get_called_class();
				if ($primaryKeyValue > 0) {
		        	$original = new $className();
		        	$original->getById($primaryKeyValue);
				}

				// If the primary key value is non-zero, do an UPDATE
				$method = $primaryKeyValue !== 0 ? 'UPDATE' : 'INSERT';
				$parameters = array();
				
				foreach ($this->_dbMap as $property => $column) {
					// Primary only gets dropped in for UPDATEs
					if (($primaryKey === $property && 'UPDATE' === $method) || $primaryKey !== $property) {
						$paramName = ':' . $property;
						
						// Serialize objects going in as JSON
						$value = $this->$property;
						if (is_object($value)) {
							$value = json_encode($value);
						}
						$params[$paramName] = $value;
						
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
				$retVal = Db::Query($query, $params);

				// Save the ID for insert
				if ('INSERT' === $method && $retVal && $retVal->count > 0) {
                    $this->$primaryKey = $retVal->insertId;
					$retVal = $retVal->count;
				} else if ('UPDATE' === $method && $retVal) {
                    $retVal = true;
                }
				
			}
			
			return $retVal;
		
		}
		
		/**
		 * Creates an object from the passed database row
		 */
		public function copyFromDbRow($obj) {
			if (property_exists($this, '_dbMap') && is_object($obj)) {
				foreach($this->_dbMap as $property => $column) {
					if (property_exists($obj, $column) && property_exists($this, $property)) {
						$this->$property = $obj->$column;
						if ($column === $this->_dbPrimaryKey) {
							$this->$property = (int) $this->$property;
						}
					}
				}
			}
		}
        
        /**
         * Gets a record from the database by the primary key
         */
        public static function getById($id) {

            $retVal = null;
            $className = get_called_class();
            $obj = new $className();
            if ($obj->_hasRequiredProperties()) {
                if (is_numeric($id)) {
                    $cacheKey = $obj->_dbTable . '_getById_' . $id;
                    $retVal = Cache::Get($cacheKey);

                    if (!$retVal) {

                        $query  = 'SELECT ' . implode(',', $obj->_dbMap) . ' FROM `' . $obj->_dbTable . '` ';
                        $query .= 'WHERE ' . $obj->_dbMap[$obj->_dbPrimaryKey] . ' = :id LIMIT 1';
                        
                        $result = Db::Query($query, [ ':id' => $id ]);
                        if ($result && $result->count === 1) {
                            $row = Db::Fetch($result);
                            $retVal = new $className($row);
                        }
                        Cache::Set($cacheKey, $retVal);
                    }
                } else {
                    throw new Exception('Primary key value must be a number');
                }

            } else {
                throw new Exception('Class must have "_dbTable", "_dbMap", and "_dbPrimaryKey" properties to use method "getById"');
            }
            return $retVal;
        }

        public function delete() {

        	$retVal = false;

        	if ($this->_hasRequiredProperties()) {
        		$primaryKey = $this->_dbPrimaryKey;
        		if ($this->$primaryKey) {
	        		$query = 'DELETE FROM ' . $this->_dbTable . ' WHERE ' . $this->_dbMap[$primaryKey] . ' = :id';
	        		$params = array( ':id' => $this->$primaryKey );
	        		$retVal = Db::Query($query, $params);
	        	}
        	}

        	return $retVal;

        }

        public static function deleteById($id) {
        	$className = get_called_class();
        	$retVal = new $className();
        	$retVal->id = $id;
        	return $retVal->delete();
        }

        private function _getAll() {
        	$retVal = null;
        	if ($this->_hasRequiredProperties()) {
        		$cacheKey = $this->_dbTable . '_getAll';
        		$retVal = Cache::Get($cacheKey);
        		if (!$retVal) {
        			$query = 'SELECT ' . implode(',', $this->_dbMap) . ' FROM `' . $this->_dbTable . '`';
        			$result = Db::Query($query);
        			if ($result && $result->count) {
        				$retVal = array();
        				$className = get_class($this);
        				while ($row = Db::Fetch($result)) {
        					$retVal[] = new $className($row);
        				}
        				Cache::Set($cacheKey, $retVal);
        			}
        		}
        	}

        	return $retVal;
        }

        /**
         * Returns all rows of a table
         */
        public static function getAll() {
            $className = get_called_class();
            $cacheKey = 'Lib\\Dal:' . $className . ':getAll';
            $retVal = Cache::Get($cacheKey);
            if (false === $retVal) {
                $retVal = new $className();
                $retVal = $retVal->_getAll();
                Cache::Set($cacheKey, $retVal, 3600);
            }
        	return $retVal;
        }

        private function _hasRequiredProperties() {
        	return property_exists($this, '_dbTable') && property_exists($this, '_dbMap') && property_exists($this, '_dbPrimaryKey');
        }
	
	}

}