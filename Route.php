<?php
/**
* @package Wells.Routes
* @subpackage Route
*/

namespace Wells\Routes;

class Route {
	
	public $uri;
	
	public $callback;
	
	public $isPublic = true;
	
	public $showInAdminMenu = false;
	
	public $showInMenus = true;
	
	public $httpMethods = array();
	
	public $defaultHttpMethods = array(HTTP_GET, HTTP_POST, HTTP_HEAD);
	
	protected $routeVars;
	
	function __construct( $uri, array $args ){
			
		$this->uri = $uri;
		
		$this->import( $args );
		
		if ( empty( $this->httpMethods ) )
			$this->httpMethods = $this->defaultHttpMethods;
	}
	
	function import( array $args ){
		
		foreach( $args as $k => $v ){
			$this->{camelcase($k)} = $v;	
		}
		
		return $this;
	}
	
	function setIsPublic( $val = true ){
		$this->isPublic = (bool) $val;
		return $this;	
	}
	
	function setShowInAdminMenu( $val ){
		$this->showInAdminMenu = (bool) $val;
		return $this;	
	}
	
	function setShowInMenus( $val ){
		$this->showInMenus = (bool) $val;
		return $this;	
	}
	
	function getUri(){
		return $this->uri;	
	}
	
	function getCallback(){
		return $this->callback;	
	}
	
	function isPublic(){
		return $this->isPublic;	
	}
	
	function getHttpMethods(){
		return $this->httpMethods;
	}
	
	function isHttpMethodAllowed( $method ){
		return in_array($method, $this->httpMethods);	
	}
	
	function getVars(){
		
		if ( ! isset( $this->routeVars ) ){
			$this->routeVars = parse_route( $this->getUri() );
		}
		
		return $this->routeVars;	
	}
	
	function buildUrl( array $vars = null ){
		return build_route_url( $this->getVars(), $vars );
	}
	
}