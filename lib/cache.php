<?php

namespace Lib {

	use Memcache;
	use Predis;

	define('CACHE_VERY_LONG', 86400);
	define('CACHE_LONG', 3600);
	define('CACHE_MEDIUM', 600);
	define('CACHE_SHORT', 60);
	define('CACHE_STATS_KEYS', 'CacheStats_keys');

	if (!defined('DISABLE_CACHE')) {
		define('DISABLE_CACHE', false);
	}

	if (!DISABLE_CACHE) {
		Cache::Connect();
	}

	// memcache class
	class Cache {

		private static $_memcache;
		private static $_redis;
		private static $_disabled = false;
		private static $_cacheStats = [];

		public static function Connect($host = 'localhost', $port = 11211) {
			self::$_memcache = new Memcache();
			if (!self::$_memcache->pconnect($host, $port)) {
				self::$_memcache = null;
			}

			self::setDisabled(isset($_GET['flushCache']));

		}

		public static function Set($key, $val, $expiration = 600) {
			$retVal = false;
			if (null != self::$_memcache && is_string($key)) {
				$retVal = self::$_memcache->set(self::_createCacheKey($key), $val, null, time() + $expiration);
			}
			return $retVal;
		}

		public static function Get($key, $forceCacheGet = false, $ignoreLogging = false) {
			$retVal = false;
			$fetchFromCache = null != self::$_memcache && is_string($key) && ($forceCacheGet || !self::$_disabled);
			if ($fetchFromCache) {
				$retVal = self::$_memcache->get(self::_createCacheKey($key));
			}

			if (!$ignoreLogging) {
				self::_logCacheStat($key, $retVal);
			}

			return $retVal;
		}

		public static function Inc($key, $inc = 1, $expiration = 600) {
			$value = self::Get($key, true, true);
			if (false !== $value) {
				self::$_memcache->increment(self::_createCacheKey($key), $inc);
			} else {
				self::Set($key, $inc, $expiration);
			}
		}

		public static function setDisabled($disabled) {
			self::$_disabled = $disabled;
		}

		/**
		 * Creates a cache key using selected values from an array of values (usually _GET)
		 */
		public static function createCacheKey($prefix, $params, $values) {
			$retVal = [ $prefix ];
			foreach ($params as $param) {
				$value = Url::Get($param, 'null', $values);
				if (is_array($value)) {
					$value = implode(',', $value);
				}
				$retVal[] = $value;
			}
			return implode('_', $retVal);
		}

		/**
		 * Attempts to get data from cache. On miss, executes the callback function, caches that value, and returns it
		 */
		public static function fetch($method, $cacheKey, $duration = CACHE_MEDIUM) {
			$retVal = self::Get($cacheKey);
			if (!$retVal && is_callable($method)) {
				$retVal = $method();
				self::Set($cacheKey, $retVal, $duration);
			}
			return $retVal;
		}

		/**
		 * Data fetcher/setter for long cache (redis)
		 */
		public static function fetchLongCache($method, $cacheKey, $force = false) {
			self::_redisConnect();
			$retVal = self::$_redis->get($cacheKey);
			if (null === $retVal || $force || self::$_disabled) {
				$retVal = $method();
				self::setLongCache($cacheKey, $retVal);
			} else {
				$retVal = unserialize($retVal);
			}
			return $retVal;
		}

		public static function setLongCache($cacheKey, $data) {
			self::_redisConnect();
			self::$_redis->set($cacheKey, serialize($data));
		}

		private static function _createCacheKey($key) {
			return CACHE_PREFIX . ':' . $key;
		}

		private static function _redisConnect() {
			if (!self::$_redis) {
				self::$_redis = new Predis\Client(REDIS_SERVER);
			}
		}

		private static function _logCacheStat($key, $value) {
			$hit = $value !== false;
			$cacheKey = 'CacheStats_' . $key . '_' . ($hit ? 'hit' : 'miss');
			self::Inc($cacheKey);

			$trackedKeys = self::Get(CACHE_STATS_KEYS, true, true);
			$trackedKeys = $trackedKeys ?: [];
			if (!isset($trackedKeys[$key])) {
				$trackedKeys[$key] = true;
				self::Set(CACHE_STATS_KEYS, $trackedKeys, 86400);
			}

		}

		public static function getCacheStats() {
			$retVal = [];
			$trackedKeys = self::Get(CACHE_STATS_KEYS, true, true);
			if ($trackedKeys) {
				foreach ($trackedKeys as $key => $val) {

					// Throw away session keys
					if (!Session::isSessionKey($key)) {
						$hits = self::Get('CacheStats_' . $key . '_hit', true, true) ?: 0;
						$misses = self::Get('CacheStats_' . $key . '_miss', true, true) ?: 0;
						$retVal[$key] = (object)[
							'hits' => $hits,
							'misses' => $misses,
							'total' => $hits + $misses
						];
					}

				}
			}
			return $retVal;
		}

	}

}
