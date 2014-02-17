<?php
/**
 * @package Wells.Routes
 * @subpackage Controller
 */

namespace Wells\Routes;
 
abstract class Controller {
	
	public $routePriority = 10;
	
	public $queryVars = array();
	
	public $routes = array();
	
	protected $request;
	
	protected $response;
	
	protected static $_instance;
	
	final public static function i(){
		if ( ! isset(static::$_instance) )
			static::$_instance = new static();
		return static::$_instance;
	}
	
	final protected function __construct(){
		
		if ( ! empty($this->routes) ){
			register_routes( $this->routes, $this->routePriority, $this->queryVars );
		}
			
		\Registry::addToGroup( 'controller', $this );
	}
	
	public function __get( $var ){
			
		if ( 'template' === $var ){
			return get_current_template();
		}
		
		return isset($this->$var) ? $this->$var : null;
	}
	
	public function setRequest( Request &$request ){
		$this->request =& $request;
	}
	
	public function getRequest(){
		return isset($this->request) ? $this->request : null;
	}
	
	public function setResponse( Response &$response ){
		$this->response =& $response;
	}
	
	public function getResponse(){
		return isset($this->response) ? $this->response : null;
	}
	
	public function setTemplate( $tmpl, array $data = null ){
		set_current_template( $tmpl, $data );
		$this->template = get_current_template();
		return $this;
	}
	
	public function setTemplateVar( $var, $val ){
		$this->template->set( $var, $val );
		return $this;
	}
	
	public function setTemplateVars( array $vars ){
		$this->template->add_vars( $vars );
	}
	
	public function respond(){
		$this->response->setBody( $this->template );
	}
	
}