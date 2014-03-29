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

####Normal
```php
// Add a route at '/login/' that calls App\UserController::login() for GET and POST requests
$router->addRoute('login', array(
	'callback' => array('App\UserController', 'login'), 
	'methods' => array('GET', 'POST')
));
```

####Endpoints
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

That was a bit repetitive - to simplify, you can set a controller to use for all routes under an endpoint:
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

###Controllers not called statically
We have defined our route callbacks using strings for the class. However, they are not called statically; if the matched route callback uses a string as the first element, the router will attempt to instantiate this class before calling the method. This way, controller callbacks are run in an object context, but the objects do not have to be instantiated unless needed.

### Dispatching
To route/dispatch the request, pass the `Phpf\Http\Request` and `Phpf\Http\Response` objects to the router's `dispatch()` method:
```php
use Phpf\Http\Request;
use Phpf\Http\Response;
$request = new Request;
$router->dispatch($request, new Response($request));
```

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

There are a few pre-registered parameters which can also be used or renamed for use in your routes:
```php
$router->addRoute('users/<user_id:int>', array(
	// ...
));
$router->addRoute('page/<anything:segment>', array( // 'segment' matches everything up to a slash
	// ...
));
```

##Callbacks

After the route has been matched, the controller method will be called using the route parameters. _Routes and callback functions must use the same parameter name._ This means that if your route contains a parameter called `user_id`, then the corresponding callback method must accept a parameter called `user_id`. 

For example:
```php
$router->addRoute('users/<user_id:int>/posts/<year:[\d]{4}>', array(
	'callback' => array('App\UserController', 'getYearPosts'),
));

// In App\UserController class:

public function getYearPosts($user_id, $year) {
	// ...
}
```

The order of the parameters does not matter - the Reflection API is used to order the parameters correctly before calling the method.
