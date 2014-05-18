<?php

namespace Phpf\Route\Exception;

class MissingParam extends \RuntimeException 
{
	
	public function setMissingParameter( $param ){
		$this->missing_parameter = $param;
		return $this;
	}
	
	public function getMissingParameter(){
		return isset($this->missing_parameter) ? $this->missing_parameter : null;
	}
	
	public function __toString(){
		return "Missing required route parameter $this->getMissingParameter().";
	}
	
}