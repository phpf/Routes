<?php

namespace Phpf\Routes\Catcher;

use Phpf\Http\Request;
use Phpf\Routes\Route;
use Phpf\Routes\Router;
use Phpf\Http\Response;

abstract class AbstractCatcher {
	
	/**
	 * Called when added to Router
	 */
	public function init( Router $router, Request $request ){
		// do stuff with router and/or request
	}
	
	/**
	 * Should return true if caught, otherwise false.
	 */
	abstract public function catchRoute( Route $route, Request $request );
	
	/**
	 * Process to do when route is caught.
	 */
	abstract public function process( Response $response );
	
}
