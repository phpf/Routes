<?php
/**
 * @package Wells
 * @subpackage Routes
 */

define('HTTP_GET', 'GET' );
define('HTTP_POST', 'POST' );
define('HTTP_PUT', 'PUT' );
define('HTTP_HEAD', 'HEAD' );
define('HTTP_DELETE', 'DELETE' );
define('HTTP_OPTIONS', 'OPTIONS' );

require 'functions.php';

class_alias( 'Wells\Routes\Request', 'Request' );
class_alias( 'Wells\Routes\Response', 'Response' );
class_alias( 'Wells\Routes\Router', 'Router' );
class_alias( 'Wells\Routes\Route', 'Route' );

Router::i()
	->setRequest( Request::i() );