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
	
	protected static $instance;
	
	public static function instance(){
		if ( ! isset(self::$instance) )
			self::$instance = new self();
		return self::$instance;
	}
	
	private function __construct(){}
	
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
		
		if ( !isset($this->request) ){
			throw new \RuntimeException("Must set Request on Router via setRequest() before dispatch() is called.");
		}
		
		$response = new Response($this->request);
		
		if ( $this->match() ){
			
			$route =& $this->route;
			$request =& $this->request;
			
			$reflect = new Callback($route->getCallback());
			
			if ( !empty($this->callback_before) ){
				$before = $this->callback_before;
				$before($route, $request, $response);
			}
			
			try {
				$reflect->reflectParameters($request->getParams());
			} catch(Exception $e){
				$this->sendError(404, 'Missing required route parameter '. $e->getMessage(), $e);
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
		return isset($this->vars[$key]) ? $this->vars[$key] : '';	
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
			$objects[ $uri ] = new Route($uri, $args);
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
	public function addRoute( $uri, array $args, $priority = 10 ){
		$route = new Route($uri, $args);
		$this->routes[$priority][$uri] = $route;
		return $this;
	}
	
	/**
	 * Alias for addRoute()
	 * @see \Phpf\Routes\Router::addRoute()
	 */
	public function route( $uri, array $args, $priority = 10 ){
		return $this->addRoute($uri, $args, $priority);
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
			return isset($this->routes[$priority]) ? $this->routes[$priority] : array();
		return $this->routes;	
	}
	
	/**
	 * Set a controller class to use for the current endpoint.
	 * @see matchEndpoints()
	 */
	public function setController( $class ){
		$this->ep_controller_class = $class;
		return $this;
	}
	
	/**
	* Matches request URI to a route.
	*/
	protected function match(){
			
		$http_method = $this->request->getMethod();
		
		// Remove content type file extensions
		$uri = $this->stripExtensions($this->request->getUri(), $type);
		
		if ( !empty($type) ){
			$this->request->content_type = $type;
		}
		
		if ( !empty($this->endpoints) ){
			if ( $this->matchEndpoints($uri, $http_method) ){
				return true;
			}
		}
		
		if ( !empty($this->routes) ){
				
			ksort($this->routes);
			
			foreach( $this->routes as $group ){
				foreach( $group as $Route ){
					if ( $this->matchRoute($Route, $uri, $http_method) ){
						return true;
					}
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Searches endpoints for route match.
	 */
	protected function matchEndpoints( $uri, $http_method ){
		
		foreach($this->endpoints as $path => $closure){
				
			if ( 0 === strpos($uri, $path) ){
				
				$this->routes[$path] = array();
				$routes = $closure($this);
				
				foreach($routes as $epUri => $array){
					
					// Closure has set a controller class to use for all routes.
					if ( isset($this->ep_controller_class) ){
						if ( is_string($array) ){
							$method = $array;
							$array = array();
							$array['callback'] = array($this->ep_controller_class, $method);
						} elseif ( is_string($array['callback']) ){
							$method = $array['callback'];
							$array['callback'] = array($this->ep_controller_class, $method);
						}
					}
					
					$array['endpoint'] = trim($path, '/');
					
					$this->routes[$path][$path.$epUri] = $route = new Route($path.$epUri, $array);
					
					if ( $this->matchRoute($route, $uri, $http_method) ){
						return true;
					}
				}
				
				unset($this->ep_controller_class);
			}
		}
		
		return false;
	}
	
	/**
	 * Determines if a given Route URI matches the request URI.
	 * If match, sets Router property $route and assembles the matched query 
	 * vars and adds them to Request property $path_params.
	 * If match but HTTP method is not allowed, sends 405 error.
	 * @TODO Add Header w/ extra info on the 405 as per HTTP/1.1
	 */
	protected function matchRoute( Route $route, $uri, $http_method ){
		
		$qvs = array();
		$route_uri = $this->parseRoute($route->uri, $qvs);
		
		if ( preg_match('#^/?' . $route_uri . '/?$#i', $uri, $route_vars) ){
			
			if ( !$route->isMethodAllowed($http_method) ){
				$this->sendError(405, "HTTP method $http_method is not permitted for this route.");
			}
			
			$this->route =& $route;
			
			unset($route_vars[0]);
			
			if ( !empty($qvs) && !empty($route_vars) ){
				$this->request->setPathParams(array_combine($qvs, $route_vars));
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Parses a route URI, changing query vars to regex and adding keys to $vars.
	 */
	protected function parseRoute( $uri, &$vars = array() ){
		
		if ( preg_match_all('/<(\w+)+:(.+?)>/', $uri, $matches) ){
			foreach($matches[0] as $i => $str){
				if ( '' !== $regex = $this->getRegex($matches[2][$i]) ){
					// Renamed: <id:int>
					$uri = str_replace($str, $regex, $uri);
					$vars[ $matches[2][$i] ] = $matches[1][$i];
				} else {
					// Inline: <year:[\d]{4}>
					$uri = str_replace($str, '(' . $matches[2][$i] . ')', $uri);
					$vars[ $matches[1][$i] ] = $matches[1][$i];
				}
			}
		}
		
		if ( preg_match_all('/<(\w+)>/', $uri, $matches) ){
			foreach($matches[0] as $i => $str){
				// Predefined: <int>
				$uri = str_replace($str, $this->getRegex($matches[1][$i]), $uri);
				$vars[ $matches[1][$i] ] = $matches[2][$i];
			}
		}
		
		return $uri;
	}
	
	/**
	* Matches filetypes at the end of a string (usually URI) and removes them.
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
