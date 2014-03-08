<?php

namespace Phpf\Routes\Catcher;

use Phpf\Routes\Route;
use Phpf\Http\Request;

abstract class Header extends AbstractCatcher {
	
	public function setCatchValue( $header ){
		$this->catch = str_replace('http', '', strtr(strtolower($header), '_', '-'));
	}
	
	public function getCatchValue(){
		return $this->catch;
	}
	
	public function catchRoute( Route $route, Request $request ){
			
		if ( isset($request->headers[$this->getCatchValue()]) ){
			return true;
		}
		
		return false;
	}
	
}
