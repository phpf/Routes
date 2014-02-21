<?php
/**
 * @package library.request
 * @subpackage functions
 */

function reflect_func_params( ReflectionFunctionAbstract $reflection, array $params ){
	
	$ordered = array();
	$parameters = array();
	
	foreach( $reflection->getParameters() as $_param )
		$ordered[ $_param->getPosition() ] = $_param;
	
	ksort($ordered);
	
	foreach( $ordered as $rParam ){
		
		$name = $rParam->getName();
		
		if ( isset($params[ $name ]) ){
			$parameters[ $name ] = $params[ $name ];
		} elseif ( $rParam->isDefaultValueAvailable() ){
			$parameters[ $name ] = $rParam->getDefaultValue();
		} else {
			throw new MissingParamException($name);
		}
	}
	
	return $parameters;
}

function invoke_closure(Closure $closure, $params = array()){
    	
    // treat as a function; cf. https://bugs.php.net/bug.php?id=65432
    $reflect = new ReflectionFunction($closure);
    
    // sequential arguments when invoking
    $args = array();
    
    // match params with arguments
    foreach ($reflect->getParameters() as $i => $param) {
        if (isset($params[$param->name])) {
            // a named param value is available
            $args[] = $params[$param->name];
        } elseif (isset($params[$i])) {
            // a positional param value is available
            $args[] = $params[$i];
        } elseif ($param->isDefaultValueAvailable()) {
            // use the default value
            $args[] = $param->getDefaultValue();
        } else {
            // no default value and no matching param
            $message = "Closure($i : \${$param->name})";
            throw new MissingParamException($message);
        }
    }
    
	return $reflect->invokeArgs($args);
}

class MissingParamException extends ReflectionException {}

/**
 * Register an array of routes.
 * 
 * @param array $routes Routes to register with callbacks, http_methods, etc.
 * @param int $priority Route priority. default 10 (optional)
 * @param array $query_vars Query vars to register (optional)
 */
function register_routes( array $routes, $priority = 10, array $query_vars = null ){
	
	if ( ! empty( $query_vars ) ){
		Wells\Routes\Router::i()->addQueryVars($query_vars);
	}
	
	Wells\Routes\Router::i()->addRoutes( $routes, $priority );
}

/**
 * Register a RouteController instance.
 */
function register_controller( Wells\Routes\Controller $object ){
	Wells\Util\Registry::addToGroup( 'controller', $object );
}

/**
 * Returns a registered RouteController instance.
 */
function get_controller( $class ){
	return Wells\Util\Registry::getFromGroup( 'controller', $class );
}

/**
 * Alias for get_controller()
 * @see get_controller()
 */
function controller( $class ){
	return get_controller( $class );
}

/**
 * Returns the Request instance.
 */
function request(){
	return Wells\Routes\Request::i();	
}

/**
 * Returns the Router instance.
 */
function router(){
	return Wells\Routes\Router::i();
}

/**
 * Returns the Response instance.
 */
function response(){
	return Wells\Routes\Response::i();
}

/**
 * Returns the current Route instance.
 */
function current_route(){
	return Wells\Routes\Request::route();
}

/**
 * Returns an array of routes matching the passed args.
 * @see Request\Router::get_routes_where()
 */
function get_routes_where( array $args, $operator = 'AND', $keys_exist_only = false ){
	return Wells\Routes\Router::i()->getRoutesWhere( $args, $operator, $keys_exist_only );
}

/**
 * Parses a route string and returns an array of its params.
 * 
 * @param string $route The route URI to parse (non-regexed)
 * @return array Associative array of the route's parameters.
 */
function parse_route( $route ){
	
	$route_vars = array();
	
	// Match query vars with renamings (e.g. ":id(post_id)") or without (e.g. ":year")
	if ( preg_match_all('/:(\w+)+(\((\w+)\))?/', $route, $matches) && !empty($matches[3]) ){
		
		foreach( $matches[3] as $i => $var_name ){
			// replace empty var names with regex key
			if ( empty( $var_name ) ){
				$matches[3][ $i ] = $matches[1][ $i ];	
			}	
		}
		
		// array of 'var key' => 'regex key'
		$route_vars = array_combine( $matches[3], $matches[1] );
	}
	
	return $route_vars;
}

/**
 * Builds a URI given a route URI and array of corresponding vars.
 * 
 * @param string|array $uri URI route string, or the array returned from parse_route()
 * @param array|null $vars The route parameters to inject into the returned URL.
 * @return string The URL to the given route with params replaced.
 */
function build_route_uri( $uri, array $vars = null ){
	
	if ( is_array($uri) ){
		$route_vars =& $uri;
	} else {
		$route_vars = parse_route( $uri );
	}
	
	if ( ! empty( $route_vars ) ){
			
		foreach( $route_vars as $key => $regex ){
			
			if ( ! isset( $vars[ $key ] ) ){
				trigger_error( "Cannot build route - missing required var '$key'." );
				return null;
			}
		
			$uri = str_replace( ':' . $regex . '(' . $key . ')', $vars[ $key ], $uri );
			$uri = str_replace( ':' . $regex, $vars[ $key ], $uri );
			$uri = str_replace( ':' . $key, $vars[ $key ], $uri );
		}
	}
	
	return $uri;
}