<?php

/**
 * PDO wrapper class
 */
 
namespace Lib {
 
	use PDO;
	use stdClass;
 
	class Db {

		/**
		 * The handle to the database connection
		 */
		private static $_conn = null;
		
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
			
			try {
				self::$_conn = new PDO($dsn, $user, $pass);
				self::$_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$retVal = true;
			} catch (PDOException $e) {
				self::$lastError = $e->Message();
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
						$retVal = self::$_conn->lastInsertId();
						break;
					case 'update':
					case 'delete':
						$retVal = $comm->rowCount();
						break;
				}
				
				self::$lastError = self::$_conn->errorInfo();
				
			} catch (Exception $e) {
				echo $sql, PHP_EOL; exit;
				self::$lastError = $e->Message();
				throw $e;
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
		
	}
}