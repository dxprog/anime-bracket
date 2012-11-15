<?php

namespace Lib {

	use Memcache;

	Cache::Connect();

	if (isset($_GET['flushCache'])) {
		Cache::flush();
	}

	// memcache class
	class Cache {

		private static $_conn;
		
		public static function Connect($host = 'localhost', $port = 11211) {
			self::$_conn = new Memcache();
			if (!self::$_conn->pconnect($host, $port)) {
				self::$_conn = null;
			}
		}
		
		public static function Set($key, $val, $expiration = 600) {
			$retVal = false;
			if (null != self::$_conn && $key) {
				$retVal = self::$_conn->set($key, $val, null, time() + $expiration);
			}
			return $retVal;
		}
		
		public static function Get($key) {
			$retVal = false;
			if (null != self::$_conn && $key && !isset($_GET['flushCache'])) {
				$retVal = self::$_conn->get($key);
			}
			return $retVal;
		}
		
		public static function Flush() {
			self::$_conn->flush();
		}

	}

}
