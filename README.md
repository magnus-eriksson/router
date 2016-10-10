# A small and simple PHP router

A one-file router with groups, prefix and before/after filters.

## Install

Clone this repository or use composer to download the library with the following command:
```
composer require maer/router dev-master
```
_Change `dev-master` to the last tagged release._

## Set up

Load the library via composers autoloader:

```
include '/path/to/vendor/autoload.php';
```

Load the library manually:

```
include '/path/to/library/src/Router.php';
```

## Simple example

```
$r = new Maer\Router\Router;

// Define a route
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

// ...or if you want to use some non-standard verb
$r->add('SOMEVERB', '/', function() {});

// ...or if you want to define multiple verbs at once
$r->add(['GET', 'POST', ...], function() {});

// Dispatch the router
$response = $r->dispatch();

echo $response;

```

## Route parameters

To set up route parameters, add a placeholder wrapped in parentheses:

```
$r->get('/something/(:alpha)', function($arg) {

});
```

Available route parameters:

|Placeholder    |Contains                         |
|---------------|---------------------------------|
|(:alphanum)    | Any alpha numeric characters    |
|(:alpha)       | Any alpha character             |
|(:num)         | Any numeric value               |
|(:any)         | Any character, except `/`       |



## Callbacks

Route callbacks can be defined in different ways:

```
// Anonymous function
$r->get('/', function() {
    // Something
});

// Class method
$r->get('/', ['Namespace\Classname', 'methodName']);
// or
$r-get('/', 'Namespace\Classname@methodName');

// Static class method
$r->get('/', 'Namespace\Classname::methodName');

```

## Filters

Every route can have one or more before and after filters. Before filters are, as you might expect, triggered before the route callback is triggered and the after filter, after.

### Defining filters
```
$r->filter('a_before_filter', function() {
    // Do some magic stuff.
});

$r->filter('an_after_filter', function() {
    // Do some magic stuff.
});
```

_You can define filter callbacks in the same way as you define route callbacks_


### Using filters

```
$r->get('/', 'Classname@methodName', [
    'before' => 'a_before_filter',
    'after' => 'an_after_filter'
]);

// Defining more filters, separating them with a `|` sign:
['before' => 'filter1|filter2|filter3|...']
```

### Filter responses

If a filter returns anything other than null, the dispatch chain will stop and that value will be returned instead. This is good for stopping the route if a specific criteria isn't met.

## Group routes

If there are many routes that will use the same filters and/or prefix (more on that later), you can group those routes together:

```
$r->group(['before' => 'a_before_filter', 'after' => 'an_after_filter'], function($r) {

    $r->get('/', function() {
        //....
    });

    // Just keep defining routes

});
```
The `$r->group()`-method only takes an anonymous function as callback. The router instance is always passed as an argument to the callback.

### Prefix

Just like with filters, there might be multiple routes with the same prefix (like for admin routes, where all starts with '/admin'). Just pass `'prefix'` to the group:

```
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

```
$response = $r->dispatch('GET', '/some/url');
```

To get the matched router without the any callbacks (both filters and route callback) being triggered, use the `$r->getMatch()` method instead.

```
$r->get('/', function() {});

$route = $this->getMatch('GET', '/');

// Returns:
// object =>
//     pattern   => "/group1"
//     callback  => object(Closure)#8 (0) {}
//     before    => ['beforefilter'],
//     after     => ['afterfilter'],
//     args      => []
//     method    => "GET"
```

If the before and after filters are closures, you can trigger them via:

```
$response = $r->executeCallback('beforefilter');
```


## More info to come...

---
If you have any questions, suggestions or issues, let me know!

Happy coding!