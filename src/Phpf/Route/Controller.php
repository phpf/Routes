<?php

namespace Phpf\Route;

use Phpf\Request;
use Phpf\Response;

/**
 * Controllers create a response from a request.
 */
abstract class Controller
{

	/**
	 * Get an object property.
	 * 
	 * @param string $var Property name.
	 * @return mixed Property value if set, otherwise null.
	 */
	public function get($var) {
		return isset($this->$var) ? $this->$var : null;
	}

	/**
	 * Set an object property
	 * 
	 * @param string $var Property name.
	 * @param mixed $value Property value.
	 * @return $this
	 */
	public function set($var, $value) {
		$this->$var = $value;
		return $this;
	}

	/**
	 * Attach an object by name.
	 *
	 * A glorified set() method, where $name is the classname
	 * unless otherwise specified.
	 *
	 * @param object $object Object to attach.
	 * @param string $name [Optional] Name to identify object; defaults to class name.
	 */
	public function attach($object, $name = '') {

		if (empty($name)) {
			$name = get_class($object);
		}

		return $this->set($name, $object);
	}
	
	/**
	 * Attaches Request and Response to controller.
	 * 
	 * @param Phpf\Request $request
	 * @param Phpf\Response $response
	 */
	public function init(Request $request, Response $response) {
		$this->set('request', $request);
		$this->set('response', $response);
		return $this;
	}
	
	/**
	 * Transfer the current object's properties to another controller.
	 * 
	 * @param \Phpf\Route\Controller &$controller Instance of controller to transfer to.
	 * @param array $exclude [Optional] Indexed array of properties to exclude from the transfer.
	 * @return $this
	 */
	public function transfer(Controller &$controller, array $exclude = null) {
		
		foreach(get_object_vars($this) as $k => &$v) {
				
			if (isset($exclude) && in_array($k, $exclude, true)) {
				continue;
			}
			
			$controller->set($k, $v);
		}
		
		return $this;
	}
	
	/**
	 * Forwards the request by calling another controller method.
	 * 
	 * @param Controller $controller Controller
	 * @param string $method Controller method name
	 * @param array $args Arguments passed to controller method.
	 */
	public function forward(Controller $controller, $method, array $args = array()) {
		
		$this->transfer($controller);
		
		if (is_callable(array($controller, $method))) {
			return call_user_func_array(array($controller, $method), $args);
		}
	}
	
}
