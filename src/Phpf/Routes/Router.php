<?php
/**
 * @package Phpf.Routes
 * @subpackage Router
 */

namespace Phpf\Routes;

use Exception;
use Phpf\Util\Reflection\Callback;

class Router {
	
	public $routeGroups = array();
	
	public $queryVars = array(
		'dir'	=> '([^_/][^/]+)',
		'w'		=> '(\w+)',
		'd'		=> '(\d+)',
		's'		=> '(.+?)',
		'any'	=> '(.?.+?)',
	);
	
	public $matchFiletypes = 'html|jsonp|json|xml|php'; // filetypes to strip from uris
	
	protected $request;
	
	protected $matches = array();
	
	protected $callback;
	
	protected $callbackParams;
	
	protected static $_instance;
	
	public static function i(){
		if ( ! isset(self::$_instance) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	private function __construct(){}
	
	/**
	 * Sets Request object.
	 */
	public function setRequest( Request $request ){
		$this->request =& $request;
	}	
	
	/**
	* Matches and routes a request URI.
	*/
	public function dispatch(){
		
		if ( $route = $this->match() ){
						
			$this->request->setRoute($route);
			
			$response = Response::i();
			$response->init();
			
			if ( is_array($route->callback) && is_object($route->callback[0]) ){
				$route->callback[0]->attach($this->request, 'request');
				$route->callback[0]->attach($response, 'response');
			}
			
			$reflection = new Callback($route->callback);
			
			$params = $this->getRouteParams($route);
			
			try {
				$reflection->reflectParameters($params);
			} catch(Exception $e){
				header('Status 404 Not Found', true, 404);
				die("Missing required route parameter " . $e->getMessage());
			}
			
			$reflection->invoke();
		#	call_user_func_array($route->callback, $callback_params);
			
			$response->send();
		}
		
		header('Status 404 Not Found', true, 404);
		die('Unknown route.');
		
	}
	
	/**
	* Adds query var and regex
	*
	* @param string $name The query var name
	* @param string $regex The var's regex, or another registered var name
	*/
	public function addQueryVar( $name, $regex ){
		$this->queryVars[ $name ] = $regex;
		return $this;	
	}
	
	/**
	* Adds array of query vars and regexes
	*/
	public function addQueryVars( array $vars ){
		foreach( $vars as $name => $regex ){
			$this->addQueryVar( $name, $regex );	
		}
		return $this;
	}
	
	/**
	* Returns array of query vars and regexes
	*/
	public function getQueryVars(){
		return $this->queryVars;	
	}
	
	/**
	* Returns regex for a query var
	*/
	public function getRegex( $key ){
		return isset( $this->queryVars[ $key ] ) ? $this->queryVars[ $key ] : null;	
	}
	
	/**
	* Returns string of query var keys separated by "|"
	* For use in a regular expression.
	*/
	public function getKeysForRegex(){
		static $keys;
		if ( ! isset( $keys ) )
			$keys = implode( '|', array_keys($this->getQueryVars()) );
		return $keys;
	}

	/**
	* Adds a group of routes.
	*
	* Group can already exist in same or other grouping (priority).
	*
	* @param string $controller The lowercase controller name
	* @param array $routes Array of 'route => callback'
	* @param int $priority The group priority level
	* @param string $position The routes' position within the group, if exists already
	*/
	public function addRoutes( array $routes, $priority = 5, $position = 'top' ){
		
		$route_objects = array();
		
		foreach( $routes as $uri => $args ){
			$route_objects[ $uri ] = new Route( $uri, $args );
		}
		
		if ( ! isset( $this->routeGroups[ $priority ] ) || empty( $this->routeGroups[ $priority ] ) ){
			// priority does not exist
			$this->routeGroups[ $priority ] = $route_objects;	
		} else {
			
			if ( 'top' === $position ){
				$the_routes = array_merge( $route_objects, $this->routeGroups[ $priority ] );
			} else {
				$the_routes = array_merge( $this->routeGroups[ $priority ], $route_objects );	
			}
			
			$this->routeGroups[ $priority ] = $the_routes;
		}
				
		return true;	
	}
	
	/**
	* Returns array of route objects, their URI as the key.
	* Can return a specified priority group, otherwise returns all.
	*/
	public function getRoutes( $priority = null ){
		
		if ( $priority !== null )
			return isset( $this->routeGroups[ $priority ] ) ? $this->routeGroups[ $priority ] : null;
		
		return $this->routeGroups;	
	}
	
	/**
	* Returns array of route objects matching conditions $args.
	*
	* @see list_filter
	*
	* @param array $args Key value pairs to compare to each list item
	* @param string $operator One of: 'AND' (default), 'OR', or 'NOT'.
	* @return array Routes matching conditions.
	*/
	public function getRoutesWhere( array $args, $operator = 'AND', $key_exists_only = false ){
		
		if ( isset($args['priority']) ){
			
			if ( ! isset( $this->routeGroups[ $args['priority'] ] ) )
				return array();
			
			// unset priority to avoid false non-matches with 'AND'
			$priority = $args['priority'];
			unset( $args['priority'] );
			
			return \Phpf\Util\Arr::filter( $this->routeGroups[ $priority ], $args, $operator );
		}
		
		$matched = array();
		foreach( $this->routeGroups as $priority => $group ){
			
			$matches = \Phpf\Util\Arr::filter( $group, $args, $operator, $key_exists_only );
			
			if ( ! empty( $matches ) )
				$matched = array_merge( $matched, $matches );
		}
		
		return $matched;
	}

	/**
	* Matches filetypes appended to string (usually URI), removes them,
	* and sets as the requested content-type if valid.
	*/
	public function matchFiletypeEnding( $string ){
		
		if ( preg_match("/[\.|\/]($this->matchFiletypes)/", $string, $matches) ){
			
			$type = $matches[1];
			$this->request->contentType = $type;
			
			// find the separator ("." or "/")
			$sep = str_replace( $type, '', $matches[0] );
			
			// remove the type and sep from the string
			$string = str_replace( $sep . $type, '', $string );
		}
		
		return $string;
	}
	
	/**
	* Matches request URI to a route.
	* Sets up Query if match and returns true
	*/
	protected function match(){
		
		// Remove content type endings
		$request_uri = $this->matchFiletypeEnding( $this->request->getUri() );
		
		$http_method = $this->request->getHttpMethod();
		
		ksort($this->routeGroups);
		
		foreach( $this->routeGroups as $group ){
				
			foreach( $group as $Route ){
				
				if ( ! $Route->isHttpMethodAllowed($http_method) )
					continue;
				
				$route_uri = $this->regexRoute($Route);
				
				if ( preg_match('#^/?' . $route_uri . '/?$#', $request_uri, $this->matches['values']) ) {
					
					unset($this->matches['values'][0]);
					
					$this->matches['keys'] = array_keys($Route->getVars());
				
					$this->matchedRoute[ $Route->uri ] = $route_uri; // just for debugging
		
					return $Route;
				}
			}
		}
		
		return false;
	}
	
	/**
	* Converts query vars to regex (e.g. ":id" -> "(\d+)")
	* 
	* @param Route $route The Route object
	* @return string The fully regexed route
	*/
	protected function regexRoute( Route $route ){
		
		$route_uri = $route->getUri();
		
		foreach( $route->getVars() as $varname => $regex_key ){
			$route_uri = str_replace(':' . $regex_key . '(' . $varname . ')', $this->getRegex($regex_key), $route_uri);
			$route_uri = str_replace(':' . $regex_key, $this->getRegex($regex_key), $route_uri);
		}
		
		return $route_uri;
	}
	
	protected function getRouteParams( Route $route ){
		
		if ( ! empty($this->matches['keys']) && ! empty($this->matches['values']) ){
			$query_vars = array_combine($this->matches['keys'], $this->matches['values']);
			$this->request->setQueryVars($query_vars);
		}
		
		return array_merge($this->request->getQueryVars(), $this->request->getParams());
	}
	
}
