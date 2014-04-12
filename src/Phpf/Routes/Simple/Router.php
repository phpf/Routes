<?php

namespace Phpf\Routes\Simple;

use Phpf\Routes\Exception\HttpMethodNotAllowed;

class Router {
	
	public $timer = array();
	
	protected $manager;
	
	protected $matches = array();
	
	public function __construct(Manager $manager) {
		$this->manager = $manager;
	}
	
	public function route($method, $uri) {
		
		$this->timer['start'] = microtime(true);
		
		$uri_segments = explode('/', trim($uri, '/'));
		$endpoint = array_shift($uri_segments);
		$uri_segment_count = count($uri_segments);
		
		// get matching endpoint
		if ($ep = $this->manager->getEndpoint($endpoint)) {
			
			// get endpoint's routes
			foreach($ep->getRoutes() as $route) {
				
				$path = trim($route->getPath(), '/');
				$path_segments = explode('/', $path);
				
				if (0 === $uri_segment_count) {
					// no segments - must be root
					if (0 === count(array_filter($path_segments))) {
						return $this->initMatch($route, $method);
					} 
					continue;
				}
				
				// different number of path segements -> no match
				if (count($path_segments) !== $uri_segment_count) {
					continue;
				}
				
				// match each route path segment
				foreach($path_segments as $i => $seg) {
					
					if (false === strpos($seg, ':')) {
						// straight text	
						if ($seg === $uri_segments[$i]) {
							$this->matches[$seg] = $uri_segments[$i];
						} else {
							break;
						}
					} elseif ($param = $this->manager->getParameter(ltrim($seg, ':'))) {
						// parameter
						if (isset($uri_segments[$i]) && $param->validate($uri_segments[$i])) {
							$this->matches[$param->name] = $uri_segments[$i];
						} else {
							break;
						}
					}
				}
				
				// check that all segments fulfilled
				if (count($this->matches) === $uri_segment_count) {
					return $this->initMatch($route, $method);
				}
			}
		}
		
		$this->endTimer();
		
		return false;	
	}

	public function getMatches() {
		return $this->matches;
	}

	protected function endTimer() {
		$this->timer['end'] = microtime(true);
		$this->timer['elapsed'] = round($this->timer['end'] - $this->timer['start'], 4);
	}

	protected function initMatch(Route $route, $request_method) {
		
		$this->endTimer();
		
		if ($route->isMethodAllowed($request_method)) {
			return $route;
		}
		
		throw new HttpMethodNotAllowed($request_method);
	}
	
}

