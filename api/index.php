<?php
/**
 * DXAPI main staging area
 * @author Matt Hackmann <matt@dxprog.com>
 * @package DXAPI
 * @license GPLv3
 */

/**
 * Used to calculate the page generation time
 * @global integer $GLOBALS['_begin']
 * @name $_begin
 */
$GLOBALS['_begin'] = microtime(true);

/**
 * The session key
 * @global $GLOBALS['_sesskey']
 * @name $_sesskey
 */
$GLOBALS['_sesskey'] = null;

/**
 * Path to the API libraries
 * @global $GLOBALS['_apiPath']
 * @name $_apiPath
 */
$GLOBALS['_apiPath'] = '../';

// Include the important libraries
require_once('../lib/dxapi.php');
Api\DxApi::initialize();

// Get the type, library and method off the query string
$type = strtolower($_GET['type']);
$method = $_GET['method'];

// Check for a cache of this call and parse out the parameters
$vars = array();
$cacheKey = 'ApiCall';
foreach ($_GET as $key=>$val) {
	$cacheKey .= '_' . $key . '=' . $val;
	if ($key != 'method' && $key != 'type') {
		$vars[$key] = $val;
	}
}

$_ret = Lib\Cache::Get($cacheKey);
if (isset($vars['key']) && isset($vars['signature']) && \Api\DxApi::checkSignature($vars)) {
	$_ret = false;
}

if (false === $_ret) {

	// Check to see if a valid return type was supplied
	if (@strpos($_return, $type) === false) {
		throw new Excpetion($_err[ERR_INVALID_RETURN_TYPE], ERR_INVALID_RETURN_TYPE);
	}

	// Get the library and function call of the incoming request. The result should have exactly two rows
	$t = explode ('.', $method, 2);
	if (count($t) != 2) {
		throw new Exception($_err[ERR_INVALID_METHOD], ERR_INVALID_METHOD);
	}
	$library = $t[0];
	$method = $t[1];

	// Parse the request and clean up
	$_ret = Api\DxApi::handleRequest($library, $method, $vars);
	Api\DxApi::clean();
	Lib\Cache::Set($cacheKey, $_ret);

}

// Begin constructing the response
Api\DxApi::constructResponse ($type, $_ret);

?>
