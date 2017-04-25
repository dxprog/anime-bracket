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
	require_once('./vendor/autoload.php');
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
	}
}
