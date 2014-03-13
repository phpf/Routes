<?php

namespace Phpf\Routes {
	
	class Functional {
		// dummy class
	}
}

namespace {
			
	/**
	 * Register an array of routes.
	 * 
	 * @param array $routes Routes to register with callbacks, http_methods, etc.
	 * @param int $priority Route priority. default 10 (optional)
	 * @param array $query_vars Associative array of {key} => {regex} pairs. These will also be registered (optional)
	 */
	function add_routes( array $routes, $priority = 10, array $query_vars = null ){
		
		if ( is_array($query_vars) )
			\Phpf\Routes\Router::instance()->addVars($query_vars);
		
		\Phpf\Routes\Router::instance()->addRoutes($routes, $priority);
	}
	
	/**
	* Returns array of route objects matching conditions $args.
	*
	* @see \Phpf\Util\Arr::filter()
	*
	* @param array $args Key value pairs to compare to each list item
	* @param string $operator One of: 'AND' (default), 'OR', or 'NOT'.
	* @return array Routes matching conditions.
	*/
	function get_routes_where( array $args, $operator = 'AND', $key_exists_only = false ){
		
		$routes = \Phpf\Routes\Router::instance()->getRoutes();
		
		if ( isset($args['priority']) ){
			
			if ( empty($routes[ $args['priority'] ]) )
				return array();
			
			// unset priority to avoid false non-matches with 'AND'
			$i = $args['priority'];
			unset($args['priority']);
			
			return \Phpf\Util\Arr::filter($routes[$i], $args, $operator);
		}
		
		$matched = array();
		foreach( $routes as $priority => $group ){
			
			$matches = \Phpf\Util\Arr::filter($group, $args, $operator, $key_exists_only);
			
			if ( !empty($matches) )
				$matched = array_merge($matched, $matches);
		}
		
		return $matched;
	}
	
	/**
	 * Register a Phpf\Routes\Controller instance.
	 */
	function set_controller( $id, \Phpf\Routes\Controller $object ){
		\Phpf\Util\Registry::set('controller.'.$id, $object);
	}
	
	/**
	 * Returns a registered Phpf\Routes\Controller instance.
	 */
	function get_controller( $id ){
		return \Phpf\Util\Registry::get('controller.'.$id);
	}
	
}
