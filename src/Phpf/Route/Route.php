<?php

namespace Phpf\Route;

use Serializable;
use RuntimeException;

/**
 * Routes represent a single URI.
 */
class Route implements Serializable
{
	/**
	 * URI path.
	 * @var string
	 */
	public $uri;

	/**
	 * HTTP methods accepted.
	 * @var array
	 */
	public $methods;

	/**
	 * Array callable with object when built.
	 * @var callable
	 */
	protected $callback;

	/**
	 * Controller class name.
	 * @var string
	 */
	protected $controller;

	/**
	 * Controller method name.
	 * @var string
	 */
	protected $action;
	
	/**
	 * Matched route variables from request URI.
	 * @var array
	 */
	protected $params;
	
	/**
	 * The URI with var placeholders replaced with regex.
	 * @var string
	 */
	protected $parsed_uri;
	
	/**
	 * Indexed array of variable names from the parsed URI.
	 * @var array
	 */
	protected $uri_vars;
	
	/**
	 * Whether to instantiate the controller class on match.
	 * @var boolean
	 */
	protected $initControllerOnMatch = true;
	
	/**
	 * Default HTTP methods.
	 * @var array
	 */
	public static $defaultMethods = array('GET', 'POST', 'HEAD', );

	/**
	 * Construct route with URI, callback/action, and accepted HTTP methods.
	 *
	 * @param string $uri Route URI
	 * @param mixed $action_callback Action or callback to run when route is invoked.
	 * @param array $methods Accepted HTTP methods for this route.
	 */
	public function __construct($uri, $action_callback, array $methods) {

		$this->uri = $uri;
		// use isset()
		$this->methods = array_combine($methods, $methods);

		if (is_array($action_callback)) {
			if (is_object($action_callback[0])) {
				$this->callback = $action_callback;
			} else {
				$this->controller = $action_callback[0];
				isset($action_callback[1]) and $this->action = $action_callback[1];
			}
		} else if (is_string($action_callback)) {
			$this->action = $action_callback;
		}
	}

	/**
	 * Returns the route URI.
	 *
	 * @return string URI path.
	 */
	public function getUri() {
		return $this->uri;
	}
	
	/**
	 * Sets the parsed URI, with regex in place of var placeholders.
	 * 
	 * @param string $str Parsed route.
	 * @return $this
	 */
	public function setParsedUri($str) {
		$this->parsed_uri = $str;
		return $this;
	}
	
	/**
	 * Returns the parsed route if set.
	 * 
	 * @return string|null Parsed route if set, otherwise null.
	 */
	public function getParsedUri() {
		return isset($this->parsed_uri) ? $this->parsed_uri : null;
	}
	
	/**
	 * Sets the route vars, likely from parsing.
	 * 
	 * @param array Indexed array of route vars.
	 * @return $this
	 */
	public function setVars(array $vars) {
		$this->uri_vars = $vars;
		return $this;
	}
	
	/**
	 * Returns an array of the route vars.
	 * 
	 * @return array Indexed array of route vars.
	 */
	public function getVars() {
		return $this->uri_vars;
	}
	
	/**
	 * Returns allowed HTTP methods.
	 *
	 * @return array Array of allowed HTTP methods.
	 */
	public function getMethods() {
		return $this->methods;
	}

	/**
	 * Returns whether a given HTTP method is allowed.
	 *
	 * @param string $method Uppercase name of HTTP method.
	 * @return boolean True if method allowed, otherwise false.
	 */
	public function isMethodAllowed($method) {
		return isset($this->methods[$method]);
	}
	
	/**
	 * Sets an associative array of route parameters from the matched request URI.
	 * 
	 * @param array $params Indexed array of path parameters.
	 * @return $this
	 */
	public function setParams(array $params) {
		if (! empty($this->uri_vars) && ! empty($params)) {
			$this->params = array_combine($this->uri_vars, $params);
		}
		return $this;
	}
	
	/**
	 * Returns route parameters set from request match.
	 * 
	 * @return null|array Associative array of parameters if set, otherwise null.
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * Sets or gets whether to initialize the controller on match.
	 *
	 * If true, a new instance of the controler class will be created
	 * when the route is matched to the request URI.
	 *
	 * @param null|boolean $newval New value to set, or null to return current value.
	 * @return boolean True if the route's controller is instantiated on match,
	 * otherwise false.
	 */
	public function initControllerOnMatch($newval = null) {
		if (isset($newval)) {
			$this->initControllerOnMatch = (bool)$newval;
		}
		return $this->initControllerOnMatch;
	}

	/**
	 * Sets the route controller.
	 *
	 * Controller can be a string (if controller is initialized on match - {@see
	 * initControllerOnMatch()}) or an object.
	 *
	 * @param string|object $controller Route controller class or object.
	 * @return $this
	 */
	public function setController($controller) {
		if (is_string($controller)) {
			$this->controller = $controller;
		} else if (is_object($controller)) {
			$this->callback[0] = $controller;
		}
		return $this;
	}
	
	/**
	 * Sets the route "action" - i.e. the controller class method.
	 * 
	 * @param string $action Callable method of controller.
	 * @return $this
	 */
	public function setAction($action) {
		$this->action = $action;
		return $this;
	}
	
	/**
	 * Returns the callback in a callable format.
	 * 
	 * Callback is built 1st time called, depending on information provided 
	 * (e.g. whether an action and/or class/object have been set).
	 * 
	 * @return callable Route callback
	 * 
	 * @throws RuntimeException if callback cannot be built.
	 */
	public function getCallback() {
		static $built;

		if (true === $built)
			return $this->callback;

		// Instantiate controller on route match (first time method is called)
		if ($this->initControllerOnMatch) {

			$class = $action = $object = false;

			if (isset($this->controller)) {
				$class = $this->controller;
			}
			if (isset($this->action)) {
				$action = $this->action;
			}

			if (! $class || ! $action) {
				if (! isset($this->callback) || ! is_callable($this->callback, true)) {
					throw new RuntimeException("Cannot create callback - insufficient information.");
				}
				$cb = $this->callback;
				
				if (is_array($cb)) {
					if (is_string($cb[0]))
						$class = $cb[0];
					else if (is_object($cb[0]))
						$object = &$this->callback[0];
					if (isset($cb[1]))
						$action = $cb[1];
				} else if (is_object($cb)) {
					$object =& $this->callback;
				} else if (is_string($cb)) {
					$action = $cb;
				}
			}

			if (! $object) {
				if (! $class) {
					throw new RuntimeException("Cannot create callback - no controller object specified.");
				}
				$object = new $class;
			}

			if (! $action) {
				if (! method_exists($object, '__invoke')) {
					throw new RuntimeException("Cannot create callback - no controller action specified.");
				}
				$action = '__invoke';
			}

			$this->callback = array($object, $action);

			$built = true;
		}

		return $this->callback;
	}

	public function serialize() {
		
		$vars = get_object_vars($this);
		
		unset($vars['callback']);
		unset($vars['params']);
		
		return serialize($vars);
	}
	
	public function unserialize($serial) {
		foreach(unserialize($serial) as $key => $val) {
			$this->$key = $val;
		}
	}

}
