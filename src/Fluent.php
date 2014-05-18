<?php

namespace Phpf\Route;

class Fluent {
	
	protected $router;
	
	public function __construct(Router &$router) {
		$this->router = $router;
	}
	
	public function controller($class) {
		$this->router->setController($class);
		return $this;
	}
	
	public function methods(array $methods) {
		$this->router->setDefaultMethods($methods);
		return $this;
	}
	
	public function param($varname, $regex) {
		$this->router->setVar($varname, $regex);
		return $this;
	}
	
	public function add($uri, $action, array $methods = array('GET','HEAD','POST'), $priority = 10) {
		$this->router->addRoute($uri, $action, $methods, $priority);
		return $this;
	}
	
	public function get($uri, $action, $priority = 10) {
		$this->router->addRoute($uri, $action, array('GET'), $priority);
		return $this;
	}
	
	public function post($uri, $action, $priority = 10) {
		$this->router->addRoute($uri, $action, array('POST'), $priority);
		return $this;
	}
	
	public function put($uri, $action, $priority = 10) {
		$this->router->addRoute($uri, $action, array('PUT'), $priority);
		return $this;
	}
	
	public function head($uri, $action, $priority = 10) {
		$this->router->addRoute($uri, $action, array('HEAD'), $priority);
		return $this;
	}
	
	public function patch($uri, $action, $priority = 10) {
		$this->router->addRoute($uri, $action, array('PATCH'), $priority);
		return $this;
	}
	
	public function delete($uri, $action, $priority = 10) {
		$this->router->addRoute($uri, $action, array('DELETE'), $priority);
		return $this;
	}
	
	public function options($uri, $action, $priority = 10) {
		$this->router->addRoute($uri, $action, array('OPTIONS'), $priority);
		return $this;
	}
	
	public function all($uri, $action, $priority = 10) {
		$methods = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');
		$this->router->addRoute($uri, $action, $methods, $priority);
		return $this;
	}
}
