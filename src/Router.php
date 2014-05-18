<?php

namespace Phpf\Route;

use Closure;

/**
 * Routes a request to a callback.
 */
class Router
{
	/**
	 * Request object.
	 * @var \Phpf\Request
	 */
	protected $request;

	/**
	 * Response object.
	 * @var \Phpf\Response
	 */
	protected $response;

	/**
	 * Matched route object.
	 * @var \Phpf\Route\Route
	 */
	protected $route;
	
	/**
	 * Event container object.
	 * @var \Phpf\EventContainer
	 */
	protected $events;
	
	/**
	 * Fluent interface for adding routes.
	 * @var \Phpf\Route\Fluent
	 */
	protected $fluent_interface;
	
	/**
	 * Route objects.
	 * @var array
	 */
	protected $routes = array();

	/**
	 * Name of the endpoint currently being executed, or false.
	 * @var string|boolean
	 */
	protected $doingEndpoint = false;
	
	/**
	 * Route query variables.
	 * @var array
	 */
	protected $vars = array(
		'segment'	=> '([^_/][^/]+)', 
		'words'		=> '(\w\-+)', 
		'integer'	=> '(\d+)',
		'float'		=> '(\d?\.\d+)',
		'string'	=> '(.+?)', 
		'wild'		=> '(.?.+?)', 
	);
	
	/**
	 * Matches a request URI and calls its method.
	 * 
	 * @param \Phpf\Request &$request Request object.
	 * @param \Phpf\Response &$response Response object.
	 * @return void
	 */
	public function dispatch($http_method, $uri) {
		
		if ($this->matchEndpoints($uri, $http_method) || $this->matchRoutes($uri, $http_method)) {
			return $this->route;
		}
		
		$exception = new RouteException("Unknown route.");
		$exception->setStatusCode(404);
		throw $exception;
	}

	/**
	 * Adds query var and regex
	 *
	 * @param string $name The query var name.
	 * @param string $regex The var's regex.
	 * @return $this
	 */
	public function setVar($name, $regex) {
		$this->vars[$name] = $regex;
		return $this;
	}
	
	/**
	 * Adds array of query vars and regexes
	 * 
	 * @param array $vars Associative array of var name/regex pairs.
	 * @return $this
	 */
	public function setVars(array $vars) {
		foreach ( $vars as $name => $regex ) {
			$this->setVar($name, $regex);
		}
		return $this;
	}

	/**
	 * Returns array of query vars and regexes
	 * 
	 * @return array Associative array of query var names and regexes.
	 */
	public function getVars() {
		return $this->vars;
	}

	/**
	 * Returns regex for a query var name.
	 * 
	 * @param string $key Query var name.
	 * @return string Regex for var if set, otherwise empty string.
	 */
	public function getRegex($key) {
		return isset($this->vars[$key]) ? $this->vars[$key] : '';
	}

	/**
	 * Creates and adds a single route object.
	 * 
	 * @see \Phpf\Route\Route
	 * 
	 * @param string $uri URI path.
	 * @param array $args Route arguments passed to constructor.
	 * @param int $priority Route priority. Default 10.
	 * @return $this
	 */
	public function addRoute($uri, $action_callback, array $methods = array('GET','HEAD','POST'), $priority = 10) {
		
		$uri = ltrim($uri, '/');
		
		if ($this->doingEndpoint) {
				
			// we are executing from within an endpoint closure!
			
			if (isset($this->ep_default_methods) && $methods == array('GET','HEAD','POST')) {
				$methods = $this->ep_default_methods;
			}
			
			$this->addEndpointRoute($this->doingEndpoint, $uri, array(
				'action' => $action_callback,
				'methods' => $methods,
			));
			
		} else {
			$this->routes[$priority][$uri] = new Route($uri, $action_callback, $methods);
		}
		
		return $this;
	}
	
	/**
	 * Returns the fluent interface for adding routes.
	 * 
	 * @return \Phpf\Route\Fluent Fluent interface instance.
	 */
	public function fluent() {
		if (! isset($this->fluent_interface)) {
			$this->fluent_interface = new Fluent($this);
		}
		return $this->fluent_interface;
	}

	/**
	 * Add a group of routes under an endpoint/namespace
	 * 
	 * @param string $path Endpoint path
	 * @param Closure $callback Closure that returns the routes
	 * @return $this
	 */
	public function endpoint($path, Closure $callback) {
		$this->endpoints[$path] = $callback;
		return $this;
	}
	
	/**
	 * Set the default HTTP methods to use for routes in the endpoint currently being executed.
	 * 
	 * @see matchEndpoints()
	 * 
	 * @param array $methods Indexed array of default HTTP methods.
	 * @return $this
	 */
	public function setDefaultMethods(array $methods) {
		$this->ep_default_methods = $methods;
		return $this;
	}
	
	/**
	 * Set a controller class to use for routes in the endpoint currently being executed
	 * 
	 * @see matchEndpoints()
	 * 
	 * @param string $class Controller classname.
	 * @return $this
	 */
	public function setController($class) {
		$this->ep_controller_class = $class;
		return $this;
	}
	
	/**
	 * Returns array of all route objects, their URI as the key.
	 * 
	 * Useful for caching routes along with the setRoutes() method,.
	 * 
	 * @return array Route objects.
	 */
	public function getRoutes() {
		static $didEndpoints;
		static $didParse;
		
		if (true === $didParse && (empty($this->endpoints) || true === $didEndpoints)) {
			return $this->routes;
		}
		
		// execute all unexecuted endpoints
		foreach ($this->endpoints as $path => $closure) {
			if (! isset($this->routes[$path])) {
				$this->doEndpoint($path, $closure);
			}
		}
		$didEndpoints = true;
		
		// parse every route and add its (parsed) URI and vars as properties
		foreach($this->routes as &$group) {
			foreach($group as &$route) {
				if (null === $route_uri = $route->getParsedUri()) {
					$vars = array(); // reset
					$parsed = $this->parseRoute($route->uri, $vars);
					$route->setParsedUri($parsed, $vars);
				}
			}
		}
		$didParse = true;
		
		return $this->routes;
	}
	
	/**
	 * Sets the router's routes, possibly from a cache.
	 * 
	 * @param array Routes
	 * @return $this
	 */
	public function setRoutes(array $routes) {
		$this->routes = $routes;
		return $this;
	}
	
	/**
	 * Creates and adds a single route object under a namespace.
	 * 
	 * @see \Phpf\Route\Route
	 * 
	 * @param string $basepath Base endpoint path.
	 * @param string $path Route path.
	 * @param array $array Keys: "controller", "action", "methods".
	 * @return boolean|Route False if too few args, otherwise new Route.
	 */
	protected function addEndpointRoute($basepath, $path, array $array) {
		
		if (! isset($array['action'])) {
			// set action from path if author is lazy
			if (! ctype_alpha($slug = trim($path, '/'))) {
				return false;
			}
			$array['action'] = $slug;
		}
		
		if (! isset($array['controller'])) {
			// are we executing within a closure that has set a
			// controller class to use for all routes?
			if (! isset($this->ep_controller_class)) {
				return false;
			}
			$array['controller'] = $this->ep_controller_class;
		}
		
		$callback = array($array['controller'], $array['action']);
		$methods = isset($array['methods']) ? $array['methods'] : array('GET','HEAD');
		
		if (! isset($this->routes[$basepath])) {
			$this->routes[$basepath] = array();
		}
		
		// create the route object
		return $this->routes[$basepath][$basepath.$path] = new Route($basepath.$path, $callback, $methods);
	}

	/**
	 * Searches endpoints for route match.
	 * 
	 * @param string $uri Request URI.
	 * @param string $http_method Request HTTP method.
	 * @return boolean True if endpoint route matched, otherwise false.
	 */
	protected function matchEndpoints($uri, $http_method) {
		
		// we have no endpoints
		if (empty($this->endpoints)) {
			return false;
		}
		
		foreach ($this->endpoints as $path => &$closure) {
				
			// check for endpoint match
			if (0 === stripos($uri, $path)) {
				
				$routes_added = $this->doEndpoint($path, $closure, $returned);
				
				if (! empty($returned) && is_object($returned)) {
					// If an object was returned, stop routing and return it.
					$this->route = $returned;
					return true;
				} else if (! $routes_added) {
					// If no routes were added, move to next endpoint
					continue;
				}
				
				// iterate through the endpoint's routes and try to match
				foreach ($this->routes[$path] as &$route) {
					if ($this->matchRoute($route, $uri, $http_method)) {
						return true;
					}
				}
			}
		}

		return false;
	}
	
	/**
	 * Searches static routes for match.
	 * 
	 * @param string $uri Request URI.
	 * @param string $http_method Request method.
	 * @return boolean True if match, otherwise false.
	 */
	protected function matchRoutes($uri, $http_method) {
		
		// we have no routes
		if (empty($this->routes)) {
			return false;
		}
		
		// sort groups by priority
		ksort($this->routes);
		
		foreach ($this->routes as $group) {
			// Iterate and match routes in each priority group
			foreach ($group as $route) {
				if ($this->matchRoute($route, $uri, $http_method)) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Execute an endpoint closure.
	 * 
	 * @param string $path Endpoint URI path.
	 * @param Closure $closure Endpoint closure.
	 * @param null &$returned Filled with closure's return value, if any.
	 * @return boolean Whether the router has routes for the given endpoint path.
	 */
	protected function doEndpoint($path, Closure $closure, &$returned = null) {
		
		// store the path for use by addRoute()
		$this->doingEndpoint = $path;
		
		// execute the closure to add its routes
		$returned = $closure($this);
		
		// reset
		$this->doingEndpoint = false;
		unset($this->ep_controller_class);
		unset($this->ep_default_methods);
				
		// return bool whether routes were added
		return ! empty($this->routes[$path]);
	}

	/**
	 * Determines if a given Route URI matches the request URI.
	 * If match, sets Router property $route and assembles the matched query
	 * vars and adds them to Request property $path_params. However, if
	 * the HTTP method is not allowed, a 405 Status error is returned.
	 * 
	 * @param Route $route The \Phpf\Route\Route object.
	 * @param string $uri Request URI.
	 * @param string $http_method Request HTTP method.
	 * @return boolean True if match and set up, otherwise false.
	 */
	protected function matchRoute(Route $route, $uri, $http_method) {
		
		// parse if unparsed
		if (null === $route_uri = $route->getParsedUri()) {
			$route_uri = $this->parseRoute($route->uri, $vars);
			$route->setParsedUri($route_uri, $vars);
		}
		
		if (preg_match('#^/?'.$route_uri.'/?$#i', $uri, $params)) {
			
			// Throw exception if HTTP method is not allowed
			if (! $route->isMethodAllowed($http_method)) {
				
				$exception = new RouteException();
				$exception->setStatusCode(405);
				$exception->setAllowedValues($route->getMethods());
				
				throw $exception;
			}
			
			// unset the full match
			unset($params[0]);

			if (! empty($params)) {
				// set path parameters
				$route->setParams($params);
			}

			$this->route = $route;

			return true;
		}

		return false;
	}

	/**
	 * Parses a route URI, changing query vars to regex and adding keys to $vars.
	 * 
	 * @param string $uri URI to parse.
	 * @param array &$vars Associative array of vars parsed from URI.
	 * @return string URI with var placeholders replaced with their corresponding regex.
	 */
	protected function parseRoute($uri, array &$vars = null) {
		
		if (! isset($vars)) {
			$vars = array();
		}
		
		if (preg_match_all('/<(\w+)(\:.+?)?>/', $uri, $route_vars)) {
			
			foreach($route_vars[1] as $idx => $varname) {
				
				if (! empty($route_vars[2][$idx])) {
					
					// has inline regex like "{name:\w+}"
					$regex = ltrim($route_vars[2][$idx], ':');
				
				} else {
					
					// var should be named
					if (! isset($this->vars[$varname])) {
						trigger_error("Unknown route var '$varname'.");
						return $uri;
					}
					
					$regex = $this->vars[$varname];
				}
				
				// Replace full string (at index 0)
				$uri = str_replace($route_vars[0][$idx], '('.$regex.')', $uri);
				
				// add route var name
				$vars[] = $varname;
			}
		}
		
		return $uri;
	}

}
