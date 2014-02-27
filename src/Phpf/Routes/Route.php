<?php
/**
* @package Phpf.Routes
* @subpackage Route
*/

namespace Phpf\Routes;

use Phpf\Http\Http;

class Route {
	
	public $uri;
	
	public $callback;
	
	public $methods = array();
	
	public static $default_methods = array(
		Http::METHOD_GET, 
		Http::METHOD_POST, 
		Http::METHOD_HEAD,
	);
	
	protected $vars;
	
	public function __construct( $uri, array $args ){
			
		$this->uri = $uri;
		
		foreach( $args as $k => $v ){
			$this->$k = $v;	
		}
		
		if ( empty($this->methods) )
			$this->methods = self::$default_methods;
		
		// Change to keys to use isset() instead of in_array()
		$this->methods = array_fill_keys($this->methods, true);
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
				if ( empty($var_name) ){
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
		
		if ( !empty($route_vars) ){
				
			foreach( $route_vars as $key => $regex ){
				
				if ( ! isset( $vars[ $key ] ) ){
					trigger_error("Cannot build route - missing required var '$key'.");
					return null;
				}
			
				$uri = str_replace( ":{$regex}({$key})", $vars[ $key ], $uri );
				$uri = str_replace( ":{$regex}", $vars[ $key ], $uri );
				$uri = str_replace( ":{$key}", $vars[ $key ], $uri );
			}
		}
		
		return $uri;
	}
	
	public function getVars(){
		
		if ( ! isset($this->vars) ){
			$this->vars = self::parse($this->uri);
		}
		
		return $this->vars;	
	}
	
	public function getUrl( array $vars = null ){
		return self::buildUri( $this->getVars(), $vars );
	}
	
	public function getMethods(){
		return $this->methods;
	}
	
	public function isMethodAllowed( $method ){
		return isset($this->methods[$method]);	
	}
	
	public function getCallback(){
		return $this->callback;	
	}
	
	public function getUri(){
		return $this->uri;	
	}
	
}