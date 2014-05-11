<?php

namespace Phpf\Routes\Simple;

use SimpleXMLElement;

class Endpoint {
	
	public $name;
	
	protected $path;
	
	protected $controller;
	
	protected $defaults = array();
	
	protected $routes = array();
	
	public function __construct(array $data) {
		
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
	}
	
	public static function newFromXml(SimpleXMLElement $xml) {
		
		$data = array();
		
		foreach(array('name', 'path', 'controller') as $attr) {
			if ($val = $xml->getAttribute($attr)) {
				$data[$attr] = $val;
			}
		}
		
		if (empty($data['path'])) {
			$data['path'] = $data['name'];
		}
		
		$data['controller'] = $xml->controller->__toString();
		
		if (isset($xml->default)) {
			$data['defaults'] = array();
			foreach($xml->default->attributes() as $key => $val) {
				$data['defaults'][$key] = $val->__toString();
			}
		}
		
		$endpoint = new static($data);
		
		foreach($xml->route as $route) {
			$endpoint->routes[] = Route::newFromXml($route)->setEndpoint($endpoint);
		}
		
		return $endpoint;
	}
	
	public function getDefault($var) {
		if (isset($this->defaults[$var])) {
			return $this->defaults[$var];
		}
		return null;
	}
	
	public function getController() {
		return $this->controller;
	}
	
	public function getRoutes() {
		return $this->routes;
	}
	
}
