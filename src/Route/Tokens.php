<?php

namespace xpl\Routing\Route;

/**
 * Container for URI tokens used by a resource's routes.
 */
class Tokens implements \Serializable
{
	protected $tokens;
	protected $default;
	protected $use_default;
	
	public function __construct(array $tokens = array(), $default_regex = '[^/]+') {
		
		$this->tokens = $tokens;
		
		if (! $default_regex) {
			$this->use_default = false;
		} else {
			$this->use_default = true;
			$this->default = $default_regex;
		}
	}
	
	public function useDefault($value) {
		$this->use_default = (bool)$value;
	}
	
	public function setDefault($regex) {
		$this->use_default or $this->use_default = true;
		$this->default = $regex;
	}
	
	public function getDefault() {
		return $this->default;
	}
	
	public function add($token, $regex) {
		$this->tokens[$token] = $regex;
		return $this;
	}
	
	public function get($token) {
			
		if (isset($this->tokens[$token])) {
			return $this->tokens[$token];
		}
		
		if ($this->use_default) {
			return $this->default;
		}
		
		return null;
	}
	
	public function exists($token) {
		return isset($this->tokens[$token]);
	}
	
	public function serialize() {
		return serialize(get_object_vars($this));
	}
	
	public function unserialize($serial) {
		foreach(unserialize($serial) as $key => $value) {
			$this->$key = $value;
		}
	}
	
}
