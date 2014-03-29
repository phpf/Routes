Routes
======

Request routing component.


###Dependencies
 * `PHP 5.3+`
 * `Phpf\Util`
 * `Phpf\Http`
 * `Phpf\Event`
 

## Basic Usage

First, initialize the router. The constructor takes one parameter, the `Phpf\Event\Container` object.
```php
use Phpf\Routes\Router;

$events = new Phpf\Event\Container;

$router = new Router($events);
```
Now, define some routes. There are two ways to define routes:

 1. The "normal" way - pass an array to the `addRoute()` method and a route is created.
 2. Under endpoints (or route namespaces) - endpoint routes are created and parsed only if the request matches the endpoint. This reduces the required parsing (and therefore, time) considerably.

#####Normal

Using the `addRoute()` method is simple:
```php
// Add a route at '/login/' that calls App\UserController::login() for GET and POST requests
$router->addRoute('login', array(
	'callback' => array('App\UserController', 'login'), 
	'methods' => array('GET', 'POST')
));
```

The above will register a route `login/` that accepts `GET` and `POST` requests. Matching requests will then call `App\UserController::login()`.


#####Endpoints
Endpoints contain a closure that is executed when the request matches the given endpoint path. The closure should return an array of the endpoint's routes.

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
			'action' => 'users'
		),
		'pages' => array(
			'action' => 'pages'
		),
		'options' => array(
			'action' => 'options'
		),
	);
});
```

Or even simpler:
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
Note, however, you can't change the HTTP methods using this last way.

###Important Note
We have defined our route callbacks using strings for the class. However, they are not called statically; if the matched route callback uses a string as the first element, the router will attempt to instantiate this class before calling the method. This way, controller callbacks are run in an object context, but the objects do not have to be instantiated unless needed.

##Route Parameters

Route parameters can be defined two ways:

1. _Inline_ - Simply add the regex inside your route like so:

```php
$router->addRoute('users/<user_id:[\d]{1,4}>', array(
	// ...
));
```

2. _Pre-registered_ - Register the variable name and regex, then use in routes:

```php
$router->addVar('user_id', '[\d]{1,4}');

$router->addRoute('users/<user_id>', array(
	// ...
));
```
