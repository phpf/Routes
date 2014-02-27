<?php
/**
 * @package Phpf.Routes
 */

namespace Phpf\Routes;

use Phpf\Http\Request;
use Phpf\Http\Response;
use Closure;
use Exception;
use Phpf\Util\Reflection\Callback;

class Router {
	
	public $routes = array();
	
	public $vars = array(
		'segment'	=> '([^_/][^/]+)',
		'words'		=> '(\w\-+)',
		'int'		=> '(\d+)',
		'str'		=> '(.+?)',
		'any'		=> '(.?.+?)',
	);
	
	/** 
	 * File extensions to strip from URLs.
	 * If matched, will set Request $content_type property
	 * and override any set by the header.
	 */
	public $strip_extensions = 'html|jsonp|json|xml|php';
	
	protected $route;
	
	protected $request;
	
	protected $callback_before;
	
	protected $callback_after;
	
	protected $error_handlers = array();
	
	protected static $_instance;
	
	public static function i(){
		if ( ! isset(self::$_instance) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	public function __construct( Request $request ){
		$this->setRequest($request);
	}
	
	/**
	 * Sets Request object.
	 */
	public function setRequest( Request $request ){
		$this->request =& $request;
	}
	
	/**
	 * Gets Request object
	 */
	public function getRequest(){
		return $this->request;
	}
	
	/**
	 * Gets matched Route object
	 */
	public function getCurrentRoute(){
		return $this->route;
	}
	
	/**
	 * Callback run before route callback is executed.
	 */	
	public function before( Closure $closure ){
		$this->callback_before = $closure;
	}
	
	/**
	 * Callback run after route callback is executed.
	 */	
	public function after( Closure $closure ){
		$this->callback_after = $closure;
	}
	
	/**
	 * Registers an error handler callback (closure).
	 */	
	public function handleError( $code, Closure $closure ){
		$this->error_handlers[$code] = $closure;
	}
	
	/**
	* Matches and routes a request URI.
	*/
	public function dispatch(){
		
		$response = new Response($this->request);
		
		if ( $this->match() ){
			
			$route =& $this->route;
			$request =& $this->request;
			
			#$this->request->setRoute($route);
			
			if ( !empty($this->callback_before) ){
				$before = $this->callback_before;
				$before($route, $request, $response);
			}
			
			$reflect = new Callback($route->callback);
			
			try {
				$reflect->reflectParameters($request->getParams());
			} catch(Exception $e){
				$this->sendError(404, 'Missing required route parameter', $e);
			}
			
			$reflect->invoke();
			
			if ( !empty($this->callback_after) ){
				$after = $this->callback_after;
				$after($route, $request, $response);
			}
		}
		
		$this->sendError(404, 'Unknown route');
	}
	
	/**
	 * Sends an error using an error handler based on status code, if exists.
	 */
	public function sendError( $code, $msg = '', $exception = null ){
		
		if ( isset($this->error_handlers[ $code ] ) ){
			$exec = $this->error_handlers[$code];
			return $exec($code, $msg, $exception, $response);
		} else {
			header(\Phpf\Http\Http::statusHeader($code), true, $code);
			die($msg);
		}
	}
	
	/**
	* Adds query var and regex
	*
	* @param string $name The query var name
	* @param string $regex The var's regex, or another registered var name
	*/
	public function addVar( $name, $regex ){
		$this->vars[ $name ] = $regex;
		return $this;	
	}
	
	/**
	* Adds array of query vars and regexes
	*/
	public function addVars( array $vars ){
		foreach( $vars as $name => $regex ){
			$this->addVar($name, $regex);
		}
		return $this;
	}
	
	/**
	* Returns array of query vars and regexes
	*/
	public function getVars(){
		return $this->vars;	
	}
	
	/**
	* Returns regex for a query var
	*/
	public function getRegex( $key ){
		return isset($this->vars[$key]) ? $this->vars[$key] : null;	
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
	public function addRoutes( array $routes, $priority = 10 ){
		
		$objects = array();
		
		foreach( $routes as $uri => $args ){
			$objects[ $uri ] = new Route( $uri, $args );
		}
		
		if ( empty($this->routes[$priority]) ){
			$this->routes[ $priority ] = $objects;	
		} else {
			$this->routes[ $priority ] = array_merge($objects, $this->routes[$priority]);
		}
				
		return true;	
	}
	
	/**
	 * Adds a single route
	 */
	public function addRoute( $uri, $callback, array $methods = array(), $priority = 10 ){
		$route = new Route( $uri, compact('callback', 'methods') );
		$this->routes[$priority][$uri] = $route;
		return $this;
	}
	
	/**
	 * Alias for addRoute()
	 * @see \Phpf\Routes\Router::addRoute()
	 */
	public function route( $uri, $callback, array $methods = array(), $priority = 10){
		return $this->addRoute($uri, $callback, $methods, $priority);
	}

	/**
	 * Add a group of routes under an endpoint/namespace
	 */
	public function endpoint( $path, Closure $callback ){
		$this->endpoints[$path] = $callback;
		return $this;
	}
	
	/**
	* Returns array of route objects, their URI as the key.
	* Can return a specified priority group, otherwise returns all.
	*/
	public function getRoutes( $priority = null ){
		if ( $priority !== null )
			return isset($this->routes[$priority]) ? $this->routes[$priority] : null;
		return $this->routes;	
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
	public function getRoutesWhere( array $args, $operator = 'AND', $key_exists_only = false ){
		
		if ( isset($args['priority']) ){
			
			if ( empty($this->routes[ $args['priority'] ]) )
				return array();
			
			// unset priority to avoid false non-matches with 'AND'
			$i = $args['priority'];
			unset($args['priority']);
			
			return \Phpf\Util\Arr::filter($this->routes[$i], $args, $operator);
		}
		
		$matched = array();
		foreach( $this->routes as $priority => $group ){
			
			$matches = \Phpf\Util\Arr::filter($group, $args, $operator, $key_exists_only);
			
			if ( !empty($matches) )
				$matched = array_merge($matched, $matches);
		}
		
		return $matched;
	}
	
	/**
	* Matches request URI to a route.
	*/
	protected function match(){
		
		// Remove content type file extensions
		$uri = $this->stripExtensions($this->request->getUri(), $type);
		
		if ( !empty($type) ){
			$this->request->content_type = $type;
		}
		
		$http_method = $this->request->getMethod();
		
		if ( !empty($this->endpoints) ){
			if ( $this->matchEndpoints($uri, $http_method) ){
				return true;
			}
		}
		
		ksort($this->routes);
		
		foreach( $this->routes as $group ){
			foreach( $group as $Route ){
				if ( $this->matchRoute($Route, $uri, $http_method) ){
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Searches endpoints for route match.
	 */
	protected function matchEndpoints( $uri, $http_method ){
		
		foreach($this->endpoints as $path => $callback){
				
			if ( 0 === strpos($uri, $path) ){
				
				$this->routes[$path] = array();
				$routes = $callback();
				
				foreach($routes as $epUri => $array){
						
					$route = new Route($path.$epUri, $array);
					
					if ( $this->matchRoute($route, $uri, $http_method) ){
						return true;
					}
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Determines if given Route matches request.
	 */
	protected function matchRoute( Route $route, $uri, $http_method ){
		
		if ( $route->isMethodAllowed($http_method) ){
			
			if (preg_match('#^/?' . $this->regexRoute($route) . '/?$#i', $uri, $matches_v)){
					
				$this->route =& $route;
				
				// unset full match and get param keys
				unset($matches_v[0]);
				$matches_k = array_keys($route->getVars());
				
				// set matched params as Request properties
				if ( !empty($matches_k) && !empty($matches_v) ){
					$this->request->setPathParams(array_combine($matches_k, $matches_v));
				}
				
				return true;
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
		
		$uri = $route->getUri();
		
		foreach( $route->getVars() as $var => $regex_key ){
			
			$uri = str_replace( ":{$regex_key}({$var})", $this->getRegex($regex_key), $uri );
			
			$uri = str_replace( ":{$regex_key}", $this->getRegex($regex_key), $uri );
		}
		
		return $uri;
	}
	
	/**
	* Matches filetypes appended to string (usually URI), removes them,
	* and sets as the requested content-type if valid.
	*/
	protected function stripExtensions( $string, &$match = null ){
		
		if ( preg_match("/[\.|\/]($this->strip_extensions)/", $string, $matches) ){
			$match = $matches[1];
			// remove extension and separator
			$string = str_replace( substr($matches[0], 0, 1).$match, '', $string );
		}
		
		return $string;
	}
	
}
