<?php

namespace xpl\Routing\Route;

use xpl\Routing\Route;

class Generator 
{
	/**
	 * @var \xpl\Routing\Route\Collection
	 */
	protected $collection;
	
	public function __construct(Collection $collection = null) {
		$this->collection = $collection ?: new Collection();
	}
	
	public function getCollection() {
		return $this->collection;
	}
	
	public function get($name, $uri, $action = null) {
		$this->collection->add(new Route($name, 'GET', $uri, $action));
		return $this;
	}
	
	public function post($name, $uri, $action = null) {
		$this->collection->add(new Route($name, 'POST', $uri, $action));
		return $this;
	}
	
	public function head($name, $uri, $action = null) {
		$this->collection->add(new Route($name, 'HEAD', $uri, $action));
		return $this;
	}
	
	public function put($name, $uri, $action = null) {
		$this->collection->add(new Route($name, 'PUT', $uri, $action));
		return $this;
	}
	
	public function delete($name, $uri, $action = null) {
		$this->collection->add(new Route($name, 'DELETE', $uri, $action));
		return $this;
	}
	
	public function options($name, $uri, $action = null) {
		$this->collection->add(new Route($name, 'OPTIONS', $uri, $action));
		return $this;
	}
	
	public function patch($name, $uri, $action = null) {
		$this->collection->add(new Route($name, 'PATCH', $uri, $action));
		return $this;
	}
	
}
