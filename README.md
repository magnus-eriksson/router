# A small and simple PHP router


[![Build Status](https://api.travis-ci.org/magnus-eriksson/router.svg)](https://travis-ci.org/magnus-eriksson/router)


A small, simple, extendable one-file PHP router with groups, filters and named routes

> I'm not claiming that this router is faster or better than other routers out there. It's kind of hard to beat something like [nikic/FastRoute](https://github.com/nikic/FastRoute). The two main reasons for building this was: 1. I wanted a simple but yet flexible, plug and play router with minimal to none setup. 2. It's fun to build stuff and you learn a lot from it!


## Usage

* [Install](#install)
* [Simple Example](#simple-example)
* [Route parameters](#route-parameters)
* [Route callbacks](#route-callbacks)
* [Filters](#filters)
* [Named routes](#named-routes)
* [Grouping routes](#grouping-routes)
    * [Group prefix](#group-prefix)
* [Dispatch the router](#dispatch-the-router)
    * [Not found](#not-found)
    * [Method not allowed](#method-not-allowed)
* [Adding a custom callback resolver](#adding-a-custom-callback-resolver)





## Install

Clone this repository or use composer to download the library with the following command:
```cli
composer require maer/router dev-master
```
_Change `dev-master` to the last tagged release._


## Simple example

```php
// Load composers autoloader
include '/path/to/vendor/autoload.php';

$r = new Maer\Router\Router;

// Define routes
$r->get('/', function() {
    return "Hello there";
});

// It also works with:
$r->post('/', function() {});
$r->put('/', function() {});
$r->delete('/', function() {});
$r->patch('/', function() {});
$r->options('/', function() {});
$r->head('/', function() {});
$r->connect('/', function() {});
$r->trace('/', function() {});
$r->any('/', function() {}); // Catches all methods

// ...or if you want to use some non-standard HTTP verb
$r->add('SOMEVERB', '/', function() {});

// ...or if you want to define multiple verbs at once
$r->add(['GET', 'POST', ...], function() {});

// Dispatch the router
$response = $r->dispatch();

echo $response;
```

## Route parameters

There are some placeholders you can use for route parameters. All parameters will be passed along to the controller in the same order as they are defined in the route:

```php
// Match any alpha [a-z] character
$r->get('/something/(:alpha)', function($param) {
    // Do stuff
});

// Match any numeric [0-9.,] character. It can also start with a -
$r->get('/something/(:num)', function($param) {
    // Do stuff
});

// Match any alphanumeric [a-z0-9] character
$r->get('/something/(:alphanum)', function($param) {
    // Do stuff
});

// Match any character (except /) [^/] character
$r->get('/something/(:any)', function($param) {
    // Do stuff
});

// Catch-all. Match all routes, including / (.*)
$r->get('/something/(:any)', function($param) {
    // Do stuff
});

// Append ? to making a parameter optional.
$r->get('/something/(:alpha)?', function($param = null){
    // Matches /something and /something/anything
});

// Combine mutliple placeholders
$r->get('/something/(:alpha)/(:any)/(:alphanum)?', function($param, $param2, $param3 = null) {
    // Do stuff
});

```

## Route callbacks

Route callbacks can be defined in different ways:

```php
// Anonymous function
$r->get('/', function() {
    // Something
});

// Class method
$r->get('/', ['Namespace\ClassName', 'methodName']);
// or
$r->get('/', 'Namespace\ClassName@methodName');

// Static class method
$r->get('/', 'Namespace\ClassName::methodName');
```
All callbacks will receive any route parameter.

_If you send in a class method (non static), the router will instantiate the class and then call the method, when the router is dispatched and a match is found._

## Filters

There are `before` and `after` filters:

```php
// Defining filters
$r->filter('myfilter', function() {
    // Do some magic stuff.
});

$r->filter('anotherfilter', function() {
    // Do some magic stuff.
});

// Add filter to your routes
$r->get('/something/', function() {

}, ['before' => 'myfilter', 'after' => 'anotherfilter']);

// Add multiple filters by combining them with |
$r->get('/something/', function() {

}, ['before' => 'myfilter|anotherfilter']);

```

Filter callbacks can be in the same formats as Route callbacks, meaning that you can use class methods as filters.

The before filter will receive all route parameter, just like the route callback. The after filter will also receive all parameters, but the first parameter will be be the response from the route callback.

**Note:** Filters will be called in the same order as they were defined. If any filter returns anything other than `null`, the dispatch will stop and that response will be returned instead.


## Named routes

Add a name to any route

```php
// Name a route
$r->get('/something', function() {

}, ['name' => 'some-page']);

// Resolve a named route
echo $r->getRoute('some-page');
// Returns: /something

// With route parameters
$r->get('/something/(:any)/(:any)', function($param1, $param2) {

});

// Resolve and pass values for the placeholders
echo $r->getRoute('some-page', ['first', 'second']);
// Returns: /something/first/second
```

If you don't pass enough arguments to cover all required parameters, an exception will be thrown.

## Grouping routes

Instead of adding the same filters over and over for many routes, it's easier to group them together.

```php
$r->group(['before' => 'a_before_filter'], function($r) {

    $r->get('/', function() {
        //....
    });

    // Just keep defining routes

});
```
The `$r->group()`-method only takes an anonymous function as callback. The router instance is always passed as an argument to the callback.

When defining a group, you can add `before` and `after` filters, just like you do for routes. You can also use a `prefix` for a group, as described below.


### Group Prefix

To add the same prefix to a group, use the `prefix` argument.

```php
$r->group(['prefix' => '/admin'], function() {

    // This matches: /admin
    $r->get('/', function() {

    });

    // This matches: /admin/something
    $r->get('/something', function() {

    });

});
```

You can mix `before`, `after` and `prefix` when creating groups.

## Dispatch the router

To dispatch the router, it's usually enough to just call the `$r->dispatch()`-method. How ever, if you want to dispatch the router with some specific URL and method you can pass them to the dispatcher (this is useful if you're writing tests):

```php
$response = $r->dispatch('GET', '/some/url');
```

If you rather trigger all the callbacks (filters and route callbacks) yourself, if you, for example, are using an IoC container, call the `$r->getMatch()` method instead and you will get the matched route object back.

```php
$r->get('/', function() {

}, ['before' => 'beforefilter', 'after' => 'afterfilter', 'name' => 'somename']);

$route = $r->getMatch('GET', '/');

// Returns:
// object =>
//     pattern   => "/group1"
//     name      => "somename",
//     callback  => object(Closure)#8 (0) {}
//     before    => ['beforefilter'],
//     after     => ['afterfilter'],
//     args      => []
//     method    => "GET"
```

If the before and after filters are closures, you can trigger them via:

```php
$response = $r->executeCallback('beforefilter');
```

### Not found

If there is no match, a `Maer\Router\NotFoundException` will be thrown. You can register a callback that will be executed instead, using the `$router->notFound()`-method:

```php
$r->notFound(function() {
    return "Ops! The page was not found!";
});

// Callbacks can be in all the same formats as for routes
```

### Method not allowed

If there is a url match but with the wrong http verb, a `Maer\Router\MethodNotAllowedException` will be thrown. You can register a callback that will be executed instead, using the `$router->methodNotAllowed()`-method:

```php
$r->methodNotAllowed(function() {
    return "Ops! Method not allowed!";
});

// Callbacks can be in all the same formats as for routes
```

## Adding a custom callback resolver

If your callback is in the format of `['Classname', 'method']`, you might want to customize how it's resolved. This is handy if you, for example, are using some kind of IoC with dependency injection.

To create your custom resolver, use the `$r->resolver()`-method. Example:

```php
$r->resolver(function($callback) use($container) {
    // The argument will always be an array with ['Class', 'method']
    return [
        $container->get($callback[0]),
        $container[1]
    ];
});
```
---
If you have any questions, suggestions or issues, let me know!

Happy coding!