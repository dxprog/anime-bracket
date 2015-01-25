<?php

namespace Lib {

    use Exception;

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
        public function sync($forceInsert = false) {

            $retVal = 0;

            if (property_exists($this, '_dbTable') && property_exists($this, '_dbMap')) {

                $dbParams = array();

                // Determine if a primary key was set
                $primaryKey = property_exists($this, '_dbPrimaryKey') ? $this->_dbPrimaryKey : false;
                $primaryKeyValue = 0;
                if ($primaryKey) {
                    $primaryKeyValue = (int) $this->$primaryKey;
                }

                // If the primary key value is non-zero, do an UPDATE
                $method = $primaryKeyValue !== 0 && !$forceInsert ? 'UPDATE' : 'INSERT';
                $parameters = [];

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
                if ('INSERT' === $method && isset($retVal->insertId)) {
                    $this->$primaryKey = $retVal->insertId;
                    $retVal = $retVal->count;
                }

            }

            return $retVal > 0;

        }

        /**
         * Performs a generic query against the database
         */
        public static function query($conditions = null, $sort = null, $limit = null, $offset = null) {

            $retVal = null;

            $obj = self::_instantiateThisObject();
            if (self::_verifyProperties($obj)) {

                $query = 'SELECT `' . implode('`, `', array_values($obj->_dbMap)) . '` FROM `' . $obj->_dbTable . '`';

                // Add WHERE
                $params = null;
                if (is_array($conditions)) {

                    $where = [];
                    $params = [];

                    foreach ($conditions as $col => $info) {

                        // Verify that the property actually exists in the map. Ensures constraint and prevents SQLi
                        if (isset($obj->_dbMap[$col])) {
                            if (is_array($info)) {

                                foreach ($info as $operator => $options) {

                                    $comparison = '=';
                                    $oper = 'AND';
                                    $value = ':' . $operator . '_' . $col;

                                    switch (strtolower($operator)) {
                                        case 'in':
                                            $value = [];
                                            for ($i = 0, $count = count($options); $i < $count; $i++) {
                                                $param = ':' . $col . $i;
                                                $params[$param] = $options[$i];
                                                $value[] = $param;
                                            }
                                            $value = '(' . implode(', ', $value) . ')';
                                            $comparison = 'IN';
                                            break;

                                        case 'lt':
                                            $params[$value] = $options;
                                            $comparison = '<';
                                            break;

                                        case 'gt':
                                            $params[$value] = $options;
                                            $comparison = '>';
                                            break;

                                        case 'like':
                                            $params[$value] = $options;
                                            $comparison = 'LIKE';
                                            break;

                                        case 'ne':
                                            $params[$value] = $options;
                                            $comparison = '!=';
                                            break;

                                        case 'null':
                                            $comparison = 'IS' . ($options ? '' : ' NOT');
                                            $value = 'NULL';
                                            break;

                                    }

                                    $where[] = $oper . ' `' . $obj->_dbMap[$col] . '` ' . $comparison . ' ' . $value;

                                }

                            // If an array wasn't passed, assume testing equality on the value with AND logic
                            } else {
                                $where[] = 'AND `' . $obj->_dbMap[$col] . '` = :' . $col;
                                $params[':' . $col] = $info;
                            }
                        } else {
                            throw new Exception('Property "' . $col . '" does not exist in DB map for table "' . $obj->_dbTable . '"');
                        }

                    }

                    // Remove the logic operator from the first item
                    $where[0] = substr($where[0], strpos($where[0], ' ') + 1);

                    $query .= ' WHERE ' . implode(' ', $where);

                }

                // Add ORDER BY
                if (is_array($sort)) {
                    $order = [];

                    foreach ($sort as $col => $direction) {

                        // Verify that the property actually exists in the map. Ensures constraint and prevents SQLi
                        if (isset($obj->_dbMap[$col])) {
                            switch (strtolower($direction)) {
                                case 'desc':
                                case 'descending':
                                    $direction = 'DESC';
                                    break;
                                default:
                                    $direction = 'ASC';
                            }
                            $order[] = '`' . $obj->_dbMap[$col] . '` ' . $direction;
                        } else {
                            throw new Exception('Property "' . $col . '" does not exist in DB map for table "' . $obj->_dbTable . '"');
                        }
                    }

                    $query .= ' ORDER BY ' . implode(', ', $order);

                }

                // Add LIMIT
                if (is_numeric($limit)) {
                    $query .= ' LIMIT ';
                    if (is_numeric($offset)) {
                        $query .= $offset . ', ';
                    }
                    $query .= $limit;
                }

                $retVal = Db::Query($query, $params);

            }

            return $retVal;

        }

        /**
         * Performs a query but resolves to database result to an array of objects
         */
        public static function queryReturnAll($conditions = null, $sort = null, $limit = null, $offset = null) {
            $retVal = null;
            $result = self::query($conditions, $sort, $limit, $offset);
            if ($result && $result->count > 0) {

                $retVal = [];
                $className = get_called_class();
                while ($row = Db::Fetch($result)) {
                    $retVal[] = new $className($row);
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
         * Deletes the object from the database
         */
        public function delete() {

            $retVal = false;

            if ($this->_verifyProperties()) {
                $primaryKey = $this->_dbPrimaryKey;
                if ($this->$primaryKey) {
                    $query = 'DELETE FROM `' . $this->_dbTable . '` WHERE ' . $this->_dbMap[$primaryKey] . ' = :id';
                    $params = array( ':id' => $this->$primaryKey );
                    $retVal = Db::Query($query, $params);
                }
            }

            return $retVal;

        }

        /**
         * Deletes an object record from the database based on ID
         */
        public static function deleteById($id) {
            $retVal = self::_instantiateThisObject();
            $retVal->id = $id;
            return $retVal->delete();
        }

        public static function getById($id) {
            $obj = self::_instantiateThisObject();
            $obj->_getById($id);
            return $obj;
        }

        /**
         * Gets a record from the database by the primary key
         */
        private function _getById($id) {

            $retVal = null;
            if (self::_verifyProperties($this)) {
                if (is_numeric($id)) {
                    $cacheKey = 'Lib:Dal:' . $this->_dbTable . '_getById_' . $id;
                    $retVal = Cache::Get($cacheKey);

                    if (!$retVal) {
                        $query  = 'SELECT `' . implode('`, `', $this->_dbMap) . '` FROM `' . $this->_dbTable . '` ';
                        $query .= 'WHERE `' . $this->_dbMap[$this->_dbPrimaryKey] . '` = :id LIMIT 1';

                        $result = Db::Query($query, [ ':id' => $id ]);
                        if (null !== $result && $result->count === 1) {
                            $this->copyFromDbRow(Db::Fetch($result));
                        }
                        Cache::Set($cacheKey, $retVal);
                    }
                } else {
                    throw new Exception('ID must be a number');
                }

            } else {
                throw new Exception('Class must have "_dbTable", "_dbMap", and "_dbPrimaryKey" properties to use method "getById"');
            }
        }

        /**
         * Instantiates an object of the current class and returns it
         */
        private static function _instantiateThisObject() {
            $className = get_called_class();
            return new $className();
        }

        /**
         * Ensures that the class has all the properties needed for these methods to work
         */
        private static function _verifyProperties($obj = null) {
            $obj = null === $obj ? self::_instantiateThisObject() : $obj;
            return property_exists($obj, '_dbTable') && property_exists($obj, '_dbMap') && property_exists($obj, '_dbPrimaryKey');
        }

    }

}