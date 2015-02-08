<?php

namespace xpl\Routing\Matcher;

use xpl\Routing\RouteInterface;

class UriStrategy implements StrategyInterface
{
	
	protected $method;
	protected $uri;
	
	public function __construct($request_method, $request_uri) {
		$this->method = strtoupper($request_method);
		$this->uri = '/'.trim($request_uri, '/');
	}
	
	/**
	 * Attempts to match a route to a request method and URI.
	 * 
	 * If matched, sets the route's parameters from the regex matches.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 * @return boolean True if the route matched, otherwise false.
	 */
	public function matchRoute(RouteInterface $route) {
		
		if ($route->getMethod() === $this->method) {
		
			if (preg_match('#^/?'.$route->getCompiledUri().'/?$#i', $this->uri, $params)) {
				
				unset($params[0]);
				
				if (! empty($params)) {
					$route->setParams($params);
				}
				
				return true;
			}
		}
		
		return false;
	}
	
}
