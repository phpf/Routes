<?php

namespace xpl\Routing;

use xpl\Foundation\RequestInterface;
use xpl\Foundation\ResponseInterface;

interface RoutableInterface 
{
	
	/**
	 * Returns a route collection.
	 * 
	 * @return \xpl\Routing\Route\Collection
	 */
	public function getRoutes();
	
	/**
	 * Called when one of the object's routes is matched.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 * @param \xpl\Foundation\RequestInterface $request
	 */
	public function onRoute(RouteInterface $route, RequestInterface $request);
	
	/**
	 * Called before response is sent.
	 * 
	 * @param \xpl\Foundation\ResponseInterface $response
	 */
	public function onRespond(ResponseInterface $response);
	
}
