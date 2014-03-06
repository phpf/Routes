<?php

namespace Phpf\Routes\Catcher;

use Phpf\Routes\Route;

abstract class Header extends AbstractCatcher {
	
	public function setCatchValue( $header ){
		$this->catch = str_replace('http', '', strtr(strtolower($header), '_', '-'));
	}
	
	public function getCatchValue(){
		return $this->catch;
	}
	
	public function catchRoute( Route $route ){
			
		if ( isset($this->request->headers[$this->getCatchValue()]) ){
			return true;
		}
		
		return false;
	}
	
}
