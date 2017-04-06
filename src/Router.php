<?php namespace Maer\Router;

use Closure;
use Exception;

class Router
{
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;

    protected $filters   = [];
    protected $prefixes  = [];
    protected $prefix    = '';
    protected $befores   = [];
    protected $before    = [];
    protected $afters    = [];
    protected $after     = [];
    protected $callbacks = [];
    protected $routes    = [];
    protected $names     = [];
    protected $notFound;
    protected $methodNotAllowed;
    protected $resolver;


    /**
     * Add a route with a specific HTTP verb
     *
     * @param  string $method
     * @param  array  $args
     *
     * @throws Exception If the method isn't one of the registerd HTTP verbs
     *
     * @return $this
     */
    public function __call($method, $args)
    {
        $method = strtoupper($method);
        $verbs  = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS', 'CONNECT', 'TRACE', 'ANY'];

        if (!in_array($method, $verbs)) {
            throw new Exception("Call to undefined method '{$method}'");
        }

        if (isset($args[0]) && is_array($args[0])) {
            if (count($args[0]) > 1) {
                if (!isset($args[2]) || !is_array($args[2])) {
                    $args[2] = [];
                }
                $args[2]['name'] = $args[0][1];
            }

            $args[0] = $args[0][0];
        }


        array_unshift($args, $method);
        return call_user_func_array([$this, 'add'], $args);
    }


    /**
     * Add a new route
     *
     * @param  string $method
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $params
     *
     * @return $this
     */
    public function add($method, $pattern, $callback, array $params = [])
    {
        $pattern = '/' . trim($pattern, '/');

        $pattern = rtrim($this->prefix . $pattern, '/');
        $pattern = $pattern ?: '/' . $pattern;

        if (!is_array($method)) {
            $method = [$method];
        }

        $before = $this->getParam($params, 'before');
        $after  = $this->getParam($params, 'after');
        $name   = $this->getParam($params, 'name');

        $before = $before ? explode('|', $params['before']) : [];
        $after  = $after  ? explode('|', $params['after']) : [];

        $this->storeRoute($method, [
            'pattern'  => $pattern,
            'name'     => $name,
            'callback' => $callback,
            'before'   => array_merge($this->before, $before),
            'after'    => array_merge($this->after, $after),
            'args'     => [],
        ]);

        return $this;
    }


    /**
     * Create a new route group
     *
     * @param  array  $params
     * @param  mixed  $callback
     *
     * @return $this
     */
    public function group(array $params, $callback)
    {
        $prefix = $this->getParam($params, 'prefix');
        $before = $this->getParam($params, 'before');
        $after  = $this->getParam($params, 'after');

        if ($prefix) {
            $this->prefixes[] = $prefix;
            $this->prefix     = '/' . trim(implode('/', $this->prefixes), '/');
        }

        if ($before) {
            $this->befores[] = $before;
            $this->before    = explode('|', implode('|', $this->befores));
        }

        if ($after) {
            $this->afters[] = $after;
            $this->after    = explode('|', implode('|', $this->afters));
        }

        call_user_func_array($callback, [$this]);

        if ($prefix) {
            array_pop($this->prefixes);
            $this->prefix = $this->prefixes ? '/' . trim(implode('/', $this->prefixes), '/') : null;
        }

        if ($before) {
            array_pop($this->befores);
            $this->before = explode('|', implode('|', $this->befores));
        }

        if ($after) {
            array_pop($this->afters);
            $this->after = explode('|', implode('|', $this->afters));
        }

        return $this;
    }


    /**
     * Get matching route
     *
     * @param  string $method
     * @param  string $path
     *
     * @throws Maer\Router\MethodNotAllowedException If the pattern was found,
     *         but with the wrong HTTP verb
     * @throws Maer\Router\NotFoudException If the pattern was not found
     *
     * @return object
     */
    public function getMatch($method = null, $path = null)
    {
        $method = $method ?: $this->getRequestMethod();
        $path   = $path ?: $this->getRequestPath();

        $method = strtoupper($method);

        $methodNotAllowed = null;

        foreach ($this->routes as $pattern => $methods) {
            preg_match($this->regexifyPattern($pattern), $path, $matches);

            if ($matches) {
                $r = $this->getRouteObject($pattern, $method);

                if (!$r) {
                    // We found a match but with the wrong method
                    $methodNotAllowed = true;
                    continue;
                }

                $r->method = strtoupper($method);
                $r->args   = $this->getMatchArgs($matches);

                return $r;
            }
        }

        if ($methodNotAllowed) {
            if ($this->methodNotAllowed) {
                return (object) [
                    'before'   => [],
                    'after'    => [],
                    'args'     => [],
                    'callback' => &$this->methodNotAllowed,
                ];
            }

            throw new MethodNotAllowedException;
        }

        if ($this->notFound) {
            return (object) [
                'before'   => [],
                'after'    => [],
                'args'     => [],
                'callback' => &$this->notFound,
            ];
        }

        throw new NotFoundException;
    }


    /**
     * Add a new route filter
     *
     * @param  string $name
     * @param  mixed  $callback
     *
     * @return $this
     */
    public function filter($name, $callback)
    {
        $this->filters[$name] = $callback;
        return $this;
    }


    /**
     * Get matching route and dispatch all filters and callbacks
     *
     * @param  string $method
     * @param  string $path
     *
     * @throws Maer\Router\MethodNotAllowedException If the pattern was found,
     *         but with the wrong HTTP verb
     * @throws Maer\Router\NotFoudException If the pattern was not found
     *
     * @return mixed
     */
    public function dispatch($method = null, $path = null)
    {
        $match = $this->getMatch($method, $path);

        foreach ($match->before as $filter) {
            if (empty($filter)) {
                continue;
            }
            $response = $this->executeCallback($filter, $match->args, true);
            if (!is_null($response)) {
                return $response;
            }
        }

        $routeResponse = $this->executeCallback($match->callback, $match->args, true);

        foreach ($match->after as $filter) {
            if (empty($filter)) {
                continue;
            }
            array_unshift($match->args, $routeResponse);
            $response = $this->executeCallback($filter, $match->args, true);
            if (!is_null($response)) {
                return $response;
            }
        }

        return $routeResponse;

    }


    /**
     * Get the requested HTTP method
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper($_SERVER['REQUEST_METHOD'])
            : null;
    }


    /**
     * Get the requested URL path
     *
     * @return string
     */
    public function getRequestPath()
    {
        return isset($_SERVER['REQUEST_URI'])
            ? '/' . trim(strtok($_SERVER['REQUEST_URI'], '?'), '/')
            : null;
    }


    /**
     * Add a callback for not found
     *
     * @param  string|Closire|array $callback
     *
     * @return $this
     */
    public function notFound($callback)
    {
        $this->notFound = $callback;
    }


    /**
     * Add a callback for method not allowed
     *
     * @param  string|Closire|array $callback
     *
     * @return $this
     */
    public function methodNotAllowed($callback)
    {
        $this->methodNotAllowed = $callback;
    }


    /**
     * Execute a callback
     *
     * @param  mixed   $cb
     * @param  array   $args
     * @param  boolean $filter Set if the callback is a filter or not
     *
     * @throws Exception If the filter is unknown
     * @throws Exception If the callback isn't in one of the accepted formats
     *
     * @return mixed
     */
    public function executeCallback($cb, array $args = [], $filter = false)
    {
        if ($cb instanceof Closure) {
            return call_user_func_array($cb, $args);
        }

        if (is_string($cb) && strpos($cb, "@") !== false) {
            $cb = explode('@', $cb);
        }

        if (is_array($cb) && count($cb) == 2) {
            if (!is_object($cb[0])) {
                $cb = $this->resolveCallback($cb);
            }
            return call_user_func_array($cb, $args);
        }

        if (is_string($cb) && strpos($cb, "::") !== false) {
            return call_user_func_array($cb, $args);
        }

        if ($filter) {
            if (!isset($this->filters[$cb])) {
                throw new Exception("Undefined filter '{$cb}'");
            }

            if (is_array($this->filters[$cb]) && count($this->filters[$cb]) == 2) {
                $this->filters[$cb] = $this->resolveCallback($this->filters[$cb]);
            }

            return call_user_func_array($this->filters[$cb], $args);
        }

        throw new Exception('Invalid callback');
    }


    /**
     * Resolve callback
     *
     * @param  callable $callback
     * @return array
     */
    protected function resolveCallback($callback)
    {
        if ($this->resolver) {
            return call_user_func_array($this->resolver, [$callback]);
        }

        return [
            new $callback[0],
            $callback[1]
        ];
    }


    /**
     * Add a resolver for callbacks of the type: ['Classname', 'method']
     * @param  callable $resolver
     * @return $this
     */
    public function resolver(callable $resolver)
    {
        $this->resolver = $resolver;
        return $this;
    }


    /**
     * Get the URL of a named route
     *
     * @param  string $name
     * @param  array  $args
     *
     * @throws Exception If there aren't enough arguments for all required parameters
     *
     * @return string
     */
    public function getRoute($name, array $args = [])
    {
        if (!isset($this->names[$name])) {
            return null;
        }

        $route   = $this->callbacks[$this->names[$name]];

        if (strpos($route->pattern, '(') === false) {
            // If we don't have any route parameters, just return the pattern
            // straight off. No need for any regex stuff.
            return $route->pattern;
        }

        // Convert all placeholders to %o = optional and %r = required
        $from    = ['/(\([^\/]+[\)]+[\?])/', '/(\([^\/]+\))/'];
        $to      = ['%o', '%r'];
        $pattern = preg_replace($from, $to, $route->pattern);

        $frags   = explode('/', trim($pattern, '/'));
        $url     = [];

        // Loop thru the pattern fragments and insert the arguments
        foreach ($frags as $frag) {
            if ($frag == '%r') {
                if (!$args) {
                    // A required parameter, but no more arguments.
                    throw new Exception('Missing route parameters');
                }
                $url[] = array_shift($args);
                continue;
            }

            if ($frag == "%o") {
                if (!$args) {
                    // No argument for the optional parameter,
                    // just continue the iteration.
                    continue;
                }
                $url[] = array_shift($args);
                continue;
            }

            $url[] = $frag;
        }

        return '/' . implode('/', $url);
    }


    /**
     * Get a value from the parameter array
     *
     * @param  array  &$params
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    protected function getParam(&$params, $key, $default = null)
    {
        return isset($params[$key]) ? $params[$key] : $default;
    }


    /**
     * Replace placeholders to regular expressions
     *
     * @param  string $pattern
     *
     * @return string
     */
    protected function regexifyPattern($pattern)
    {
        $pattern = str_replace('/(', "(/", $pattern);

        $from = ['\:all\\', '\:alphanum\\', '\:alpha\\', '\:num\\', '\:any\\', '\?', '\(', '\)'];
        $to   = ['.*', '[a-zA-Z0-9]+', '[a-zA-Z]+', '[\-]?[\d\,\.]+', '[^\/]+', '?', '(', ')'];
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace($from, $to, $pattern);

        return "/^$pattern$/";
    }


    /**
     * Get matched route from pattern and method
     *
     * @param  string $pattern
     * @param  string $method
     *
     * @return object|null
     */
    protected function getRouteObject($pattern, $method)
    {
        foreach ([$method, 'ANY'] as $verb) {
            if (array_key_exists($verb, $this->routes[$pattern])) {
                $index = $this->routes[$pattern][$verb];
                return $this->callbacks[$index];
            }
        }

        return false;
    }


    /**
     * Get and clean route arguments
     *
     * @param  array  $match
     *
     * @return array
     */
    protected function getMatchArgs(array $match)
    {
        // Remove the first element, the matching regex
        array_shift($match);

        // Iterate thru the arguments and remove any unwanted slashes
        foreach ($match as &$arg) {
            $arg = trim($arg, '/');
        }

        return $match;
    }


    /**
     * Store a route in the route collection
     *
     * @param  array  $methods
     * @param  array  $route
     */
    protected function storeRoute(array $methods, array $route)
    {
        $this->callbacks[] = (object) $route;
        $index = count($this->callbacks) - 1;

        if (!isset($this->routes[$route['pattern']])) {
            $this->routes[$route['pattern']] = [];
        }

        if ($route['name']) {
            $this->names[$route['name']] = $index;
        }

        foreach ($methods as $method) {
            $this->routes[$route['pattern']][strtoupper($method)] = $index;
        }
    }
}
