<?php

namespace Lib {
  class Query {

    private $_tableName;
    private $_fieldMap;
    private $_primaryIdField;
    private $_fields = [];
    private $_countField = null;
    private $_where = [];
    private $_order = [];
    private $_group = [];
    private $_maximum = -1;
    private $_offset = -1;
    private $_paramCounter = 0;
    private $_params = [];

    public function __construct($tableName, $fieldMap, $primaryIdField) {
      $this->_tableName = $tableName;
      $this->_fieldMap = $fieldMap;
      $this->_primaryIdField = $primaryIdField;
    }

    public function select($fields) {
      $fields = !is_array($fields) ? [ $fields ] : $fields;
      foreach ($fields as $field) {
        $field = $this->_getTableFieldFromProperty($field);
        $this->_fields[] = $field;
      }
      return $this;
    }

    public function count($fieldName) {
      if (preg_match('/[\W]/', $fieldName)) {
        throw new Error('Invalid COUNT field name: "' . $fieldName . '"');
      }
      $this->_countField = $fieldName;
    }

    public function where($field, $condition) {
      $field = $this->_getTableFieldFromProperty($field);

      if (is_array($condition)) {

        foreach ($condition as $operator => $options) {
          $comparison = '=';
          $oper = 'AND';
          $value = null;

          switch (strtolower($operator)) {
            case 'in':
              $value = [];
              for ($i = 0, $count = count($options); $i < $count; $i++) {
                $param = $this->_addParameter($options[$i]);
                $value[] = $param;
              }
              $value = '(' . implode(', ', $value) . ')';
              $comparison = 'IN';
              break;

            case 'lt':
              $value = $this->_addParameter($options);
              $comparison = '<';
              break;

            case 'gt':
              $value = $this->_addParameter($options);
              $comparison = '>';
              break;

            case 'like':
              $value = $this->_addParameter($options);
              $comparison = 'LIKE';
              break;

            case 'ne':
              $value = $this->_addParameter($options);
              $comparison = '!=';
              break;

            case 'null':
              $comparison = 'IS' . ($options ? '' : ' NOT');
              $value = 'NULL';
              break;

          }

          $this->_where[] = ' `' . $field . '` ' . $comparison . ' ' . $value;

        }

      // If an array wasn't passed, assume testing equality on the value
      } else {
        $param = $this->_addParameter($condition);
        $this->_where[] = ' `' . $field . '` = ' . $param;
      }

      return $this;
    }

    public function orderBy($field, $direction = 'ASC') {
      $direction = strtoupper($direction);
      if ($direction !== 'ASC' && $direction !== 'DESC') {
        throw new Error('Cannot order "' . $field . '" by "' . $direction . '". Must be "ASC" or "DESC".');
      }
      $field = $this->_getTableFieldFromProperty($field);
      $this->_order[] = '`' . $field . '` ' . $direction;
      return $this;
    }

    public function groupBy($fields) {
      $fields = !is_array($fields) ? [ $fields ] : $fields;
      foreach ($fields as $field) {
        $field = $this->_getTableFieldFromProperty($field);
        $this->_group[] = $field;
      }
      return $this;
    }

    public function limit($maximum, $offset = 0) {
      $this->_maximum = (int) $maximum;
      $this->_offset = (int) $offset;
      return $this;
    }

    public function build() {
      $query = 'SELECT ';

      if ($this->_countField) {
        $query .= 'COUNT(1) AS ' . $this->_countField;
        $query .= count($this->_fields) ? ', ' : ' ';
      }

      if (count($this->_fields)) {
        $query .= '`' . implode('`,`', $this->_fields) . '`';
      } else {
        $query .= '*';
      }

      $query .= ' FROM `' . $this->_tableName . '`';

      if (count($this->_where)) {
        // TODO: come up with something that can do more complex conditions
        $query .= ' WHERE ' . implode(' AND ', $this->_where);
      }

      if (count($this->_group)) {
        $query .= ' GROUP BY `' . implode('`, `', $this->_group) . '`';
      }

      if (count($this->_order)) {
        $query .= ' ORDER BY ' . implode(', ', $this->_order);
      }

      if ($this->_maximum > -1) {
        $query .= ' LIMIT ' . $this->_maximum . ', ' . $this->_offset;
      }

      return (object)[
        'sql' => $query,
        'params' => $this->_params
      ];
    }

    public function execute() {
      $query = $this->build();
      return Db::Query($query->sql, $query->params);
    }

    private function _getTableFieldFromProperty($field) {
      if (!isset($this->_fieldMap[$field])) {
        throw new Error('Field "'. $field . '" was not found on table "' . $this->_tableName . '"');
      }
      return $this->_fieldMap[$field];
    }

    private function _addParameter($value) {
      $paramName = 'param' . $this->_paramCounter++;
      $this->_params[$paramName] = $value;
      return ':' . $paramName;
    }

  }
}