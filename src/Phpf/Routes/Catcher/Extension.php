<?php

namespace Phpf\Routes\Catcher;

use Phpf\Routes\Route;
use Phpf\Http\Request;

abstract class Extension extends AbstractCatcher {
	
	public function setCatchValue( $ext ){
		$this->catch = ltrim($ext, '.');
	}
	
	public function getCatchValue(){
		return $this->catch;
	}
	
	public function catchRoute( Route $route, Request $request ){
		
		if ( $this->getCatchValue() === $request->content_type ){
			// matched extension will be set as content type
			return true;
		}
		
		return false;
	}
	
	
}
