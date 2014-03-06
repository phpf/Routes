<?php

namespace Phpf\Routes\Catcher;

use Phpf\Routes\Route;

abstract class Extension extends AbstractCatcher {
	
	public function setCatchValue( $ext ){
		$this->catch = ltrim($ext, '.');
	}
	
	public function catchRoute( Route $route ){
		
		if ( $this->catch === $this->request->content_type ){
			// matched extension will be set as content type
			return true;
		}
		
		return false;
	}
	
	
}
