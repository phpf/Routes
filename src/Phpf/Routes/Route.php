<?php
/**
* @package Phpf.Routes
*/

namespace Phpf\Routes;

use Phpf\Http\Http;

class Route {
	
	public $uri;
	
	public $methods = array();
	
	public $callback;
	
	public $init_on_match = true;
	
	public static $default_methods = array(
		Http::METHOD_GET, 
		Http::METHOD_POST, 
		Http::METHOD_HEAD,
	);
	
	public function __construct( $uri, array $args ){
			
		$this->uri = $uri;
		
		foreach( $args as $k => $v ){
			$this->$k = $v;	
		}
		
		if ( empty($this->methods) )
			$this->methods = self::$default_methods;
		
		// Change to keys to use isset() instead of in_array()
		$this->methods = array_fill_keys($this->methods, true);
	}
	
	public function getUri(){
		return $this->uri;	
	}
	
	public function getMethods(){
		return array_keys($this->methods);
	}
	
	public function isMethodAllowed( $method ){
		return isset($this->methods[$method]);	
	}
	
	public function getCallback(){
		
		static $built;
		
		if ( !isset($built) ) 
			$built = false;
		
		if ( $built )
			return $this->callback;
		
		// Instantiate the controller on route match.
		// This means don't have to create a bunch of objects
		// in order to receive the request in object context.
		if ( $this->init_on_match && is_array($this->callback) 
			&& isset($this->action) && isset($this->controller) ) 
		{
			$class = $this->controller;	
			$this->callback[0] = new $class();
			
			if ( isset($this->endpoint) ){
				$name = $this->endpoint;
			} else {
				$name = substr($this->uri, strrpos($this->uri, '/'));
			}
			
			\Registry::set('controller.'.$name, $this->callback[0]);
		}
		
		$built = true;
		
		return $this->callback;	
	}
	
}
