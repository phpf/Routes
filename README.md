Routes
======

Request routing component.


###Dependencies
 * `PHP 5.3+`
 * `Phpf\Util`
 * `Phpf\Http`
 

## Basic Usage

First, initialize the router and set the current request:
```php
use Phpf\Routes\Router;
use Phpf\Http\Request;

$router = Router::instance();

$router->setRequest(new Request);
```

Now, define some routes. There are two ways to define routes:

 1. The normal way - just pass the array arguments and a route is created.
 2. Under endpoints (or route namespaces) - routes are created and parsed only if the request matches the endpoint.

#####Normal
```php
$router->addRoute('login', array(
	'callback' => array('App\UserController', 'login'), 
	'methods' => array('GET', 'POST')
), 50);
```

The above will register a route `login/` that accepts `GET` and `POST` requests. Matching requests will be routed to `App\UserController::login()`. The route is registered with a priority of 50, meaning that routes with priorities _lower_ than 50 will be parsed _before_ it. The default priority is 10.


#####Endpoints
Endpoints are closures that are executed when the request matches the given endpoint. They return an array of routes under the endpoint.

In this example, for any request that begins with `admin/`, the router will execute the closure and attempt to match the returned routes. Note that `$router` is passed by the router itself. 
```php
$router->endpoint('admin', function ($router) {
	
	return array(
		'users' => array(
			'callback' => array('App\AdminController', 'users')
		),
		'pages' => array(
			'callback' => array('App\AdminController', 'pages')
		),
		'options' => array(
			'callback' => array('App\AdminController', 'options')
		),
	);
});
```

This would register the routes `admin/users`, `admin/pages`, and `admin/options`. 

That was a bit repetitive - to simplify, you can set a controller class to use for all routes under an endpoint like so:
```php
$router->endpoint('admin', function ($router) {
	
	$router->setController('App\AdminController');
	
	return array(
		'users' => array(
			'callback' => 'users'
		),
		'pages' => array(
			'callback' => 'pages'
		),
		'options' => array(
			'callback' => 'options'
		),
	);
});
```

That's better, but still too much typing for me. One more time:
```php
$router->endpoint('admin', function ($router) {
	
	$router->setController('App\AdminController');
	
	return array(
		'users' => 'users',
		'pages' => 'pages',
		'options' => 'options',
	);
});
```

That's better. Note, however, you can't change the HTTP methods using this last way.

##Important Notes
We have defined our route callbacks using strings for the class. However, they are not called statically; if the matched route callback uses a string as the first element, the router will attempt to instantiate this class before calling the method. This way, controller callbacks are run in an object context, but the objects do not have to be instantiated unless needed.
