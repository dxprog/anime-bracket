<?php

/**
 * dxprog.com PHP library
 */

// Load the client specific configs, then boot over to where the core lives
require_once('app-config.php');
chdir(CORE_LOCATION);
require_once('config.php');

// Used to keep track of page generation time
$_begin = microtime (true);

// Include base libraries
require_once('./lib/aal.php');

// Set the time zone
date_default_timezone_set('America/Chicago');

Lib\Session::start();

// Check for the controller and either throw a 404 or let the controller do its thing
$route = Lib\Url::getRoute();
if (class_exists('Controller\\' . $route->controller, true)) {
	call_user_func([ 'Controller\\' . $route->controller, 'render' ], $route->route);
// Catch the route being passed as the second part of the path to catch `bracket-perma/route` style URLs
// Eventually, this will be the default, but is a hack for now.
} else if (count($route->route) && class_exists('Controller\\' . $route->route[0], true)) {
	$controller = array_shift($route->route);
	array_unshift($route->route, $route->controller);
	call_user_func([ 'Controller\\' . $controller, 'render' ], $route->route);
// For any unrecognized URL with a "controller" in the route, pass over to the
// Bracket view in the hopes that it's a bracket slud. If that bracket can't
// be found, that controller will 404 the whole request
} else {
	Controller\Bracket::render(array_merge([ $route->controller ], $route->route));
}

// Render the page to output
Lib\Display::render($route);

// Calculate the amount of time it took to generate the page
$genTime = microtime (true) - $GLOBALS['_begin'];
echo "<!--\n\tGenerated in ", $genTime, " seconds.\n\tAPI hits - ", $_apiHits, ".\n\tMax memory used - ", memory_get_peak_usage(), "\n-->";

Api\DxApi::clean();
