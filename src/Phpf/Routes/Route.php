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
		
	/**
	 * Parses a route string and returns an array of its params.
	 * 
	 * @param string $route The route URI to parse (non-regexed)
	 * @return array Associative array of the route's parameters.
	 */
	public static function parse( $route ){
		$route_vars = array();
		
		// Match query vars with renamings (e.g. ":id(post_id)") or without (e.g. ":year")
		if ( preg_match_all('/:(\w+)+(\((\w+)\))?/', $route, $matches) && !empty($matches[3]) ){
			
			foreach( $matches[3] as $i => $var_name ){
				// replace empty var names with regex key
				if ( empty( $var_name ) ){
					$matches[3][ $i ] = $matches[1][ $i ];	
				}	
			}
			
			// array of 'var key' => 'regex key'
			$route_vars = array_combine( $matches[3], $matches[1] );
		}
		
		return $route_vars;
	}
		
	/**
	 * Builds a URI given a route URI and array of corresponding vars.
	 * 
	 * @param string|array $uri URI route string, or the array returned from parse_route()
	 * @param array|null $vars The route parameters to inject into the returned URL.
	 * @return string The URL to the given route with params replaced.
	 */
	public static function buildUri( $uri, array $vars = null ){
		
		if ( is_array($uri) ){
			$route_vars =& $uri;
		} else {
			$route_vars = self::parse( $uri );
		}
		
		if ( ! empty( $route_vars ) ){
				
			foreach( $route_vars as $key => $regex ){
				
				if ( ! isset( $vars[ $key ] ) ){
					trigger_error( "Cannot build route - missing required var '$key'." );
					return null;
				}
			
				$uri = str_replace( ':' . $regex . '(' . $key . ')', $vars[ $key ], $uri );
				$uri = str_replace( ':' . $regex, $vars[ $key ], $uri );
				$uri = str_replace( ':' . $key, $vars[ $key ], $uri );
			}
		}
		
		return $uri;
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
		
		if ( ! isset($this->routeVars) ){
			$this->routeVars = self::parse( $this->getUri() );
		}
		
		return $this->routeVars;	
	}
	
	public function getUrl( array $vars = null ){
		return self::buildUri( $this->getVars(), $vars );
	}
	
}