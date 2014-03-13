<?php

namespace Phpf\Routes\Exception;

class MissingRouteParameter extends \RuntimeException 
{
	
	public function setMissingParameter( $param ){
		$this->missing_parameter = $param;
		return $this;
	}
	
	public function getMissingParameter(){
		return isset($this->missing_parameter) ? $this->missing_parameter : null;
	}
	
	public function getMessage(){
		return "Missing required route parameter $this->getMissingParameter().";
	}
	
	public function __toString(){
		return $this->getMessage();
	}
	
}