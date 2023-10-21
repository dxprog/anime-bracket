<?php

/**
 * PDO wrapper class
 */

namespace Lib {

	use PDO;
	use PDOException;
	use stdClass;

	class Db {

		/**
		 * The handle to the database connection
		 */
		public static $_conn = null;

		/**
		 * The value of the last error message
		 */
		public static $lastError = '';

		/**
		 * Opens a connection to the database
		 */
		public static function Connect($dsn, $user = '', $pass = '')
		{
			$retVal = false;

			if (!defined('DB_DISABLE')) {
				self::$_conn = new PDO($dsn, $user, $pass, array( PDO::MYSQL_ATTR_FOUND_ROWS => true ));
				self::$_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$retVal = true;
			}

			return $retVal;
		}

		/**
		 * Executes a query
		 */
		public static function Query($sql, $params = null)
		{
			$retVal = null;

			try {
				$comm = self::$_conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				$comm->execute($params);

				switch (strtolower(current(explode(' ', $sql)))) {
					case 'call':
					case 'select':
						$retVal = new stdClass();
						$retVal->count = $comm->rowCount();
						$retVal->comm = $comm;
						break;
					case 'insert':
						$retVal = new stdClass;
						$retVal->insertId = self::$_conn->lastInsertId();
						$retVal->count = $comm->rowCount();
						break;
					case 'update':
					case 'delete':
						$retVal = $comm->rowCount();
						break;
				}

				self::$lastError = self::$_conn->errorInfo();

			} catch (PDOException $e) {
				self::$lastError = $e;
				$retVal = false;
			}

			return $retVal;
		}

		/**
		 * Fetches the next row in a record set
		 */
		public static function Fetch($rs)
		{
			$retVal = null;

			if (is_object($rs) && null != $rs->comm) {
				$retVal = $rs->comm->fetchObject();
			}

			return $retVal;
		}

        /**
         * Do bulk insert/update using transaction for faster processing
         * @param $transactions
         */
		public static function BulkQuery($transactions)
        {
            self::$_conn->beginTransaction();
            try {
                foreach ($transactions as $transaction) {
                    $sql = $transaction[0];
                    $params = $transaction[1];

                    $comm = self::$_conn->prepare($sql);
                    $comm->execute($params);
                }
                self::$_conn->commit();
            } catch (\Exception $e) {
                self::$_conn->rollback();
            }
        }

	}
}
