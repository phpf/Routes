<?php
/**
 * @package Wells
 * @subpackage Routes
 */
 
#Autoloader::instance('Routes', __DIR__)->register();

define('HTTP_GET', 'GET' );
define('HTTP_POST', 'POST' );
define('HTTP_PUT', 'PUT' );
define('HTTP_HEAD', 'HEAD' );
define('HTTP_DELETE', 'DELETE' );
define('HTTP_OPTIONS', 'OPTIONS' );

#require 'Request.php';
#require 'Controller.php';
require 'functions.php';

class_alias( 'Wells\Routes\Request', 'Request' );
@class_alias( 'Wells\Routes\Response', 'Response' );
class_alias( 'Wells\Routes\Router', 'Router' );
@class_alias( 'Wells\Routes\Route', 'Route' );

Router::i( Request::i() );

/**
 * @TODO Move default route controller to a core module.
 */
register_routes( array(
	'' => array(
		'callback' => 'cms_default_index',
		'http_methods' => array(HTTP_GET, HTTP_POST, HTTP_PUT, HTTP_HEAD, HTTP_OPTIONS),
		'meta_title' => 'Custom CMS',
	),
), 100 );

function cms_default_index(){

	$tmpl = current_template( 'index' )
		->import( array(
			'meta_title' => 'Custom CMS',
			'title' => 'Welcome',
			'content' => '<span class="h3">Welcome to the Custom CMS!</span><hr>',
			'footer' => '<hr><span class="text-muted">' . cms_load_stats(false) . '</span>',
		) );
		
	Response::i()->setBody($tmpl);
}

