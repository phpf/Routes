<?php
/**
* @package Phpf.Routes
* @subpackage Route
*/

namespace Phpf\Routes;

class Route {
	
	public $uri;
	
	public $callback;
	
	public $public = true;
	
	public $showInAdmin = false;
	
	public $httpMethods = array();
	
	public static $defaultHttpMethods = array(HTTP_GET, HTTP_POST, HTTP_HEAD);
	
	protected $routeVars;
	
	public function __construct( $uri, array $args ){
			
		$this->uri = $uri;
		
		$this->import( $args );
		
		if ( empty( $this->httpMethods ) )
			$this->httpMethods = self::$defaultHttpMethods;
	}
	
	public function import( array $args ){
		
		foreach( $args as $k => $v ){
			$this->{camelcase($k)} = $v;	
		}
		
		return $this;
	}
	
	public function getUri(){
		return $this->uri;	
	}
	
	public function getCallback(){
		return $this->callback;	
	}
	
	public function isPublic(){
		return $this->public;	
	}
	
	public function getHttpMethods(){
		return $this->httpMethods;
	}
	
	public function isHttpMethodAllowed( $method ){
		return in_array($method, $this->httpMethods);	
	}
	
	public function getVars(){
		
		if ( ! isset( $this->routeVars ) ){
			$this->routeVars = parse_route( $this->getUri() );
		}
		
		return $this->routeVars;	
	}
	
	public function buildUri( array $vars = null ){
		return build_route_uri( $this->getVars(), $vars );
	}
	
}