<?php
/**
 * @package Phpf.Routes
 * @subpackage Request
 */

namespace Phpf\Routes;

class Request {
	
	public $httpMethod;
	
	public $uri;
	
	public $queryString;
	
	public $headers = array();
	
	public $params = array();
	
	public $queryVars = array();
	
	public $xhr = false;
	
	public $route;
	
	public static $built;
	
	protected static $_instance;
	
	static public function i(){
		if ( !isset( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	protected function __construct(){
		self::$built = false;
	}
	
	/**
	* Get current request Route object
	*/
	public function route(){
		$_this = self::i();
		return isset( $_this->route ) ? $_this->route : null;
	}
	
	/**
	* Set current request Route object
	*/
	public function setRoute( Route &$route ){
		$this->route =& $route;
	}
	
	/**
	* Build the request
	*/
	public function build( &$server = null ){
		
		if ( self::$built ) return false;
		
		if ( empty($server) ) $server =& $_SERVER;
		
		$uri = $server['REQUEST_URI'];
		$query = $server['QUERY_STRING'];
		
		if ( !empty($query) ){ // Remove query string from uri
			$uri = str_replace( '?' . $query, '', $uri );
		}
		
		$this->uri = $this->filterUriComponent( $uri );
		$this->queryString = $this->filterUriComponent( $query );
		$this->headers = get_request_headers();
		
		$http_method = $server['REQUEST_METHOD'];
		
		switch( $http_method ){
			case 'GET':
				$this->params = $_GET;
				break;
			case 'POST':
				$this->params = $_POST;
				break;
			case 'PUT':
			case 'HEAD':
			case 'OPTIONS':
			case 'DELETE': // really shouldn't have params...
				parse_str( file_get_contents('php://input'), $this->params );
				break;
		}
		
		// allow method override header or param
		if ( isset( $server['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ){
			$http_method = $server['HTTP_X_HTTP_METHOD_OVERRIDE'];
		}
		if ( isset( $this->params['_method'] ) ){
			$http_method = $this->filterUriComponent( $this->params['_method'] );
		}
		
		$this->httpMethod = strtoupper( trim($http_method) );
		
		// Is this an XML HTTP request? (i.e. ajax)
		if ( isset( $server['HTTP_X_REQUESTED_WITH'] ) ){
			$this->xhr = (bool) 'XMLHttpRequest' === $server['HTTP_X_REQUESTED_WITH'];
		}
		
		self::$built = true;
	}
	
	/**
	* Import array of data as object properties
	*/
	public function import( array $vars = null ){
		
		if ( empty( $vars ) ) return;
		
		foreach( $vars as $var => $val ){
			
			$this->set( urldecode($var), htmlentities( strip_tags(urldecode($val)), ENT_COMPAT ) );
		}
	}
		
	/**
	* Returns property or parameter value if exists
	*/
	public function get( $var ){
		
		if ( isset( $this->$var ) )
			return $this->$var;
		
		if ( isset( $this->params[ $var ] ) )
			return $this->params[ $var ];
		
		return null;	
	}
	
	/**
	* Set a property or parameter
	*/
	public function set( $var, $val ){
		
		if ( empty( $var ) || is_numeric( $var ) )
			$this->setParam( null, $val );
		else 
			$this->$var = $val;
		
		return $this;
	}
	
	/**
	* Set a parameter
	*/
	public function setParam( $var, $val ){
		
		if ( empty( $var ) || is_numeric( $var ) )
			$this->params[] = $val;
		else 
			$this->params[ $var ] = $val;
			
		return $this;
	}
	
	/**
	* Set an array of data as parameters
	*/
	public function setParams( array $args ){
		
		foreach( $args as $k => $v ){
			$this->setParam( $k, $v );
		}
		
		return $this;	
	}
	
	/**
	* Returns the request HTTP method.
	*/
	public function getHttpMethod(){
		return $this->httpMethod;	
	}
	
	/**
	* Returns the request URI.
	*/
	public function getUri(){
		return $this->uri;	
	}
	
	/**
	* Returns the request query string if set.
	*/
	public function getQueryString(){
		return $this->queryString;	
	}
	
	/**
	* Returns all parameter values
	*/
	public function getParams(){
		return $this->params;
	}
	
	/**
	* Returns a parameter value
	*/
	public function getParam( $name ){
		return isset( $this->params[ $name ] ) ? $this->params[ $name ] : null;
	}
	
	/**
	 * Alias for Request\Request::get_param()
	 * @see Request\Request::get_param()
	 */
	public function param( $name ){
		return $this->getParam( $name );	
	}
	
	/**
	* Returns array of parsed headers
	*/
	public function getHeaders(){
		return $this->headers;	
	}
	
	/**
	* Returns a single HTTP header if set.
	*/
	public function getHeader( $name ){
		return isset( $this->headers[ $name ] ) ? $this->headers[ $name ] : null;	
	}
	
	/**
	 * Sets current route query vars.
	 */
	public function setQueryVars( array $vars ){
		$this->queryVars = $vars;
		return $this;
	}
	
	/**
	* Returns array of matched query var keys and values
	*/
	public function getQueryVars(){
		return $this->queryVars;
	}
	
	/**
	* Returns a query var value
	*/
	public function getQueryVar( $var ){
		return isset( $this->queryVars[ $var ] ) ? $this->queryVars[ $var ] : null;	
	}
	
	/**
	 * Alias for Request\Request::get_query_var()
	 * @see Request\Request::get_query_var()
	 */
	public function queryVar( $var ){
		return $this->getQueryVar( $var );	
	}
	
	/**
	* Returns true if is a XML HTTP request
	*/
	public function isXhr(){
		return (bool)$this->xhr;	
	}
	
	/**
	 * Alias for Request\Request::is_xhr()
	 * @see Request\Request::is_xhr()
	 */
	public function isAjax(){
		return $this->isXhr();	
	}
	
	/**
	* Strips naughty text and slashes from uri components
	*/
	protected function filterUriComponent( $str ){
		return trim( htmlentities( strip_tags($str), ENT_COMPAT ), '/' );	
	}
	
}