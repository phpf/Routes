<?php

namespace Phpf\Routes\Service;

class Provider implements \Phpf\Service\Provider {
	
	public function isProvided(){
		return defined('HTTP_GET');
	}
	
	public function provide(){
		
		define('HTTP_GET', 'GET');
		define('HTTP_POST', 'POST');
		define('HTTP_PUT', 'PUT');
		define('HTTP_HEAD', 'HEAD');
		define('HTTP_OPTIONS', 'OPTIONS');
		define('HTTP_PATCH', 'PATCH');
		
		$request = \Phpf\Routes\Request::i();
		$request->build();
		
		\Phpf\Routes\Router::i()->setRequest($request);
	}
	
}
