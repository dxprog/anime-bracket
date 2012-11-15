<?php

namespace Api {

	use Exception;
	use Lib;
	use stdClass;
	
	/**
	 * DxApi
	 * @author Matt Hackmann <matt@dxprog.com>
	 * @package DXAPI
	 * @license GPLv3
	 */
	require_once($_apiPath . 'config.php');
	 
	/**
	 * Data return types
	 * @global integer $GLOBALS["_return"]
	 * @name $_return
	 */
	$GLOBALS['_return'] = 'json,xml,php';

	/**
	 * Error code constants
	 */
	define('ERR_INVALID_METHOD', 100);
	define('ERR_INVALID_RETURN_TYPE', 101);
	define('ERR_INVALID_LIBRARY', 102);
	define('ERR_INVALID_FUNCTION', 103);
	define('ERR_BAD_LOGIN', 104);
	define('ERR_NEED_SESSION', 105);
	define('INVALID_KEY', 400);
	define('NO_SIGNATURE', 401);
	define('INVALID_SIGNATURE', 402);
	
	/**
	 * Error messages associate with the above error codes
	 * @global array $GLOBALS['_err']
	 * @name $_err
	 */
	$_err = array (	ERR_INVALID_METHOD=>'An invalid method was invoked.',
					ERR_INVALID_RETURN_TYPE=>'Return type requested is not valid. Valid return types are: ' . $_return,
					ERR_INVALID_LIBRARY=>'The library requested does not exists.',
					ERR_INVALID_FUNCTION=>'Function called does not exist in requested library.',
					ERR_BAD_LOGIN=>'User name and/or password do not match any on record.',
					ERR_NEED_SESSION=>'This page requires a valid user to be logged in.');

	// Set the time zone
	date_default_timezone_set('America/Chicago');

	class DxApi {
		
		private static $_initialized = false;
		
		/**
		 * Sets up callbacks and establishes a database connection
		 */
		public static function initialize() {
		
			// Register the class auto loader
			spl_autoload_register('Api\\DxApi::classLoader');
			
			// Open up a connection to the database
			Lib\Db::Connect('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS);
			
			set_exception_handler('Api\\DxApi::exceptionHandler');
			
			self::$_initialized = true;
		
		}
		
		/**
		 * Class auto loader
		 */
		private static function classLoader($library) {
			
			global $_apiPath;
			
			$library = explode('\\', $library);
			$filePath = $_apiPath;
			foreach ($library as $piece) {
				$filePath .= '/' . strtolower($piece);
			}
			$filePath .= '.php';
			
			if (is_readable($filePath)) {
				require_once($filePath);
			}
			
		}
		
		/**
		 * Handles incoming API requests and delivers them to the correct module
		 */
		public static function handleRequest($library, $method, $vars, $object = null) {
			
			global $_err, $_apiPath, $_method;
			
			// If this class hasn't yet initialized, do that first
			if (!self::$_initialized) {
				self::initialize();
			}
			
			$_method = $library . '.' . $method;

			// Make sure the function being called exists
			if (!method_exists ('Api\\' . $library, $method)) {
				throw new Exception($_err[ERR_INVALID_FUNCTION], ERR_INVALID_FUNCTION);
			}
			
			// We're all through with error checks. Call the function and gather the results
			$obj = call_user_func(array('Api\\' . $library, $method), $vars, $object);
			return self::buildObject(0, 'OK', $obj);
		
		}
		
		/**
		 * Builds the output object
		 */
		public static function buildObject($code, $msg, $response)
		{

			global $_begin;

			// Calculate the generation time
			$genTime = microtime(true) - $_begin;

			// Construct the headers and return
			$obj = new stdClass();
			$obj->metrics = new stdClass();
			$obj->status = new stdClass();
			$obj->metrics->timestamp = gmdate('U');
			$obj->metrics->gen_time = $genTime;
			$obj->status->method = isset($_GET['method']) ? $_GET['method'] : 'none';
			$obj->status->ret_code = $code;
			$obj->status->message = $msg;
			$obj->body = $response;
			return $obj;
			
		}
		
		/**
		 * Cleans up an resources left open by previous functions
		 */
		public static function clean() {
			
		}

		/**
		 * Returns the error along with the appropriate HTTP error code and ends code execution.
		 * @param integer $code The code of the error to be raised.
		 * @param string $msg The error message to be sent along with the code. Will try to pull from $_err if not defined.
		 */
		public static function exceptionHandler($exception) {

			global $_type, $_method;
			
			// Generate the output based on the type. Default to XML
			switch (strtolower ($_type)) {
				case 'json':
					$out = '{' . self::_constructJSON($exception->getCode(), $exception->getMessage()) . '}';
					$content = 'javascript';
					break;
				default:
					$out = '<?xml version="1.0"?><response>' . self::_constructXML($exception->getCode(), $exception->getMessage()) . '</response>';
					$content = 'xml';
					break;
			}
			
			// Send along the output and halt script execution
			header ('Content-Type: application/' . $content . '; charset=utf-8');
			echo $out;
			exit;
			
		}

		/**
		 * Constructs the response based upon the return type
		 */
		public static function constructResponse($type, $response)
		{
			
			global $_begin;
			
			// Separate the method output into something usable
			if (is_array ($response)) {
				$ret = $response['code'];
				$msg = $response['message'];
				$out = $response['body'];
			}
			else {
				$ret = 0;
				$msg = 'OK';
				$out = $response;
			}
			
			// If the response is blank, set it to null
			if (!$out) {
				$out = 'null';
			}
			
			// Structure of output object:
			// Head
			// - Timestamp: current unix timestamp)
			// - Gen_time: amount of time it took to generate the page
			// Status
			// - Method: the method called by the user
			// - Code: The return code of the called function
			// - Message: A message to go along with the return code
			// Body
			// - The output generated by the called method
			$obj = $response;
			
			switch (strtolower ($type)) {
				
				case 'json':
					$write = json_encode($obj);
					
					// Include the JSONP callback if it was specified
					if (isset($_GET['callback'])) {
						$write = $_GET['callback'] . '(' . $write . ')';
					}
					
					// Set the content type
					$content = 'javascript';
					break;
					
				case 'xml':
					
					// Serialize the output object to XML
					$ser = new Lib\SerializeXML();
					$write = $ser->serialize($obj, 'response');
					unset($ser);
					
					// Set the content type
					$content = 'xml';
					break;
					
				case 'php':
				default:
					$write = serialize($obj);
					$content = 'plain';
					break;
				
			}
			
			// Write the contents
			header ('Content-Type: text/' . $content . '; charset=utf-8');
			echo ($write);
			
		}

		private static function _constructXML ($code, $msg)
		{

			global $_begin, $_method;
			
			// Calculate the generation time
			$genTime = microtime (true) - $_begin;

			// Construct the headers and return
			$out = "<metrics timestamp=\"".gmdate ("U")."\" gen_time=\"$genTime}\" />\n";
			$out .= '<status><method>' . $_method . '</method><ret_code>' . $code . '</ret_code><message><![CDATA[' . $msg . ']]></message></status>';
			return $out;
			
		}

		/**
		 * Verifies an incoming signed request
		 */
		public static function checkSignature($vars, $raiseError = true) {
			
			$retVal = false;
			
			// If this is an internal call, no verification is required
			if (defined('API_LOCATION') && API_LOCATION == '_internal') {
				return true;
			}
			
			// Check to make sure the key is valid
			$key = isset($vars['key']) ? $vars['key'] : false;
			$key = strlen($key) == 32 && preg_match('/[a-f0-9]{32}/', $key) ? $key : false;
			
			if ($key) {
				
				$signature = isset($vars['signature']) ? $vars['signature'] : false;
				if ($signature) {
					
					// Get the user's secret from the database
					$params = array(':key' => $key);
					$row = Lib\Db::Fetch(Lib\Db::Query('SELECT api_secret FROM api_keys WHERE api_id=:key', $params));
					if ($row) {
					
						// Sort all the variables by key and create the signature key
						ksort($vars);
						$sig = '';
						foreach ($vars as $key=>$val) {
							if ($key != 'signature') {
								$sig .= $key . '=' . $val . '&';
							}
						}
						$sig = substr($sig, 0, strlen($sig) - 1);
						$sig = base64_encode(hash_hmac('sha256', $sig, $row->api_secret));
						
						// Drumroll
						if ($sig == $signature) {
							$retVal = true;
						} else {
							if ($raiseError) {
								throw new Exception('Signature is invalid', INVALID_SIGNATURE);
							}
						}
					
					} else {
						if ($raiseError) {
							throw new Exception('Provided key is not registered', INVALID_KEY);
						}
					}
				
				} else {
					if ($raiseError) {
						throw new Exception('This request requires a signature', NO_SIGNATURE);
					}
				}
				
			} else {
				if ($raiseError) {
					throw new Exception('Provided key is invalid', INVALID_KEY);
				}
			}
			
			return $retVal;
			
		}
		
	}
}