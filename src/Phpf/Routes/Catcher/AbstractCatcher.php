<?php

namespace Phpf\Routes\Catcher;

use Phpf\Http\Request;
use Phpf\Routes\Route;
use Phpf\Routes\Router;
use Phpf\Http\Response;

abstract class AbstractCatcher {
	
	protected $request;
	
	protected $route;
	
	final public function setRequest( Request &$request ){
		$this->request =& $request;
	}
	
	/**
	 * Called when added to Router
	 */
	public function init( Router $router ){
		// do stuff with router
	}
	
	/**
	 * Should return true if caught, otherwise false.
	 */
	abstract public function catchRoute( Route $route );
	
	/**
	 * Process to do when route is caught.
	 */
	abstract public function process( Response $response );
	
}
