<?php
/**
 * @package Wells.Routes
 * @subpackage Controller
 */

namespace Phpf\Routes;
 
abstract class Controller {
	
	protected static $_instance;
	
	final public static function i(){
		if ( ! isset(static::$_instance) )
			static::$_instance = new static();
		return static::$_instance;
	}
	
	/**
	 * Get an object property.
	 */
	public function get( $var ){
		return isset($this->$var) ? $this->$var : null;
	}
	
	/**
	 * Set an object property
	 */
	public function set( $var, $value ){
		$this->$var = $value;
		return $this;
	}
	
	/**
	 * Attach an object by name.
	 * 
	 * A glorified set() method, where $name is the classname 
	 * unless otherwise specified.
	 * 
	 * Useful to decorate "Request" and "Response" objects.
	 */
	public function attach( $object, $name = '' ){
			
		if ( empty($name) ){
			$name = get_class($object);
		}
		
		return $this->set($name, $object);
	}
	
}