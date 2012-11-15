<?php

/**
 * DX API Abstraction Layer
 */

namespace Lib {

	use Api;
	use Exception;
	use Lib;

	define('API_LOCATION', '_internal');

	/**
	 * Path to the API libraries
	 * @global $GLOBALS['_apiPath']
	 * @name $_apiPath
	 */
	$GLOBALS['_apiPath'] = './';

	/**
	 * Number of times the API layer was hit
	 * @global $GLOBALS['_apiHits']
	 * @name $_apiHits
	 */
	$GLOBALS['_apiHits'] = 0;

	// Include the DX API libraries
	require_once('./lib/dxapi.php');
	Api\DxApi::initialize();

	class Dx {
		
		private static $_initialized = false;
		
		private static function initialize() {
			if (!self::$_initialized) {
				spl_autoload_register('Lib\\Dx::classLoader');
				self::$_initialized = true;
			}
		}
		
		/**
		 * Class auto loader
		 */
		private static function classLoader($library) {
			
			$library = explode('\\', $library);
			$filePath = '.';
			foreach ($library as $piece) {
				$filePath .= '/' . strtolower($piece);
			}
			$filePath .= '.php';
			if (is_readable($filePath)) {
				require_once($filePath);
			}
			
		}
		
		/**
		 * Makes an internal or external API call based upon whether an API url was passed
		 */
		public static function call($module, $method, $params = null, $cache = 600, $apiUri = null) {		
		
			self::initialize();
			$retVal = null;
			
			if ($apiUri === null) {
				$retVal = self::_internal($module, $method, $params, $cache);
			} else {
				$retVal = self::_external($module, $method, $params, $cache, $apiUri);
			}
			
			return $retVal;
			
		}
		
		/**
		 * Makes a POST request to the API
		 */
		public static function post($module, $method, $params, $object) {
			self::initialize();
			$retVal = Api\DxApi::handleRequest($module, $method, $params, $object);
			return $retVal;
		}
		
		/**
		 * Wrapper to get a KVP via the API
		 * @param string $key Name of option to retrieve
		 * @return mixed Value of the key returned
		 */
		public static function getOption($key) {
			$obj = self::call('kvp', 'get', array('key'=>$key));
			return $obj->body;
		}
		
		/**
		 * Wrapper to set a KVP
		 * @param string $key Name of option to set
		 * @param mixed $value Value to store
		 * @return Returns the success of the set
		 */
		public static function setOption($key, $value) {
			return self::post('kvp', 'set', array('key'=>$key), $value);
		}
		
		/**
		 * Makes an internal API call
		 */
		private static function _internal($module, $method, $params, $cache) {
			global $_apiHits;
			$cacheKey = md5($module . '-' . $method . '-' . serialize($params));
			$retVal = Lib\Cache::Get($cacheKey);
			if ($retVal === false || $cache == 0) {
				$_apiHits++;
				$retVal = Api\DxApi::handleRequest($module, $method, $params);
				Lib\Cache::Set($cacheKey, $retVal);
			}
			return $retVal;
		}
		
		/**
		 * Makes an API call to another instance of DxApi via REST
		 */
		private static function _external($module, $method, $params, $cache, $apiUri) {
			
			global $_apiHits;
			
			$qs = '/index.php?type=json&method=' . $module . '.' . $method;

			// Build the query string
			if (count($params) > 0) {
				foreach ($params as $key=>$val) {
					$qs .= "&$key=".urlencode($val);
				}
			}

			// Check to see if there is a cached version of this
			$cacheKey = md5($apiUri.$qs);
			$retVal = Cache::Get($cacheKey);
			if ($retVal === false || $cache == 0) {
				$_apiHits++;
				$file = file_get_contents($apiUri . $qs);
				$retVal = json_decode($file);
				// Only cache on success
				if ($retVal->status->ret_code == 0) {
					Cache::Set($cacheKey, $retVal, $cache);
				}
			}

			// Return the request
			return $retVal;
		
		}

	}

}