<?php
/**
 * @package Phpf.Routes
 * @subpackage functions
 */

/**
 * Register an array of routes.
 * 
 * @param array $routes Routes to register with callbacks, http_methods, etc.
 * @param int $priority Route priority. default 10 (optional)
 * @param array $query_vars Query vars to register (optional)
 */
function register_routes( array $routes, $priority = 10, array $query_vars = null ){
	
	if ( !empty($query_vars) )
		Phpf\Routes\Router::i()->addQueryVars($query_vars);
	
	Phpf\Routes\Router::i()->addRoutes($routes, $priority);
}

/**
 * Returns the Request instance.
 */
function request(){
	return Phpf\Routes\Request::i();	
}

/**
 * Returns the Router instance.
 */
function router(){
	return Phpf\Routes\Router::i();
}

/**
 * Returns the Response instance.
 */
function response(){
	return Phpf\Routes\Response::i();
}

/**
 * Returns the current Route instance.
 */
function current_route(){
	return Phpf\Routes\Request::route();
}

/**
 * Returns an array of routes matching the passed args.
 * @see Request\Router::get_routes_where()
 */
function get_routes_where( array $args, $operator = 'AND', $keys_exist_only = false ){
	return Phpf\Routes\Router::i()->getRoutesWhere($args, $operator, $keys_exist_only);
}

/**
 * Parses a route string and returns an array of its params.
 * 
 * @param string $route The route URI to parse (non-regexed)
 * @return array Associative array of the route's parameters.
 */
function parse_route( $route ){
	return \Phpf\Routes\Route::parse($route);
}

/**
 * Builds a URI given a route URI and array of corresponding vars.
 * 
 * @param string|array $uri URI route string, or the array returned from parse_route()
 * @param array|null $vars The route parameters to inject into the returned URL.
 * @return string The URL to the given route with params replaced.
 */
function build_route_uri( $uri, array $vars = null ){
	return \Phpf\Routes\Route::buildUri($uri, $vars);
}