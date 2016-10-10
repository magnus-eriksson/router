<?php namespace Maer\Router;

use Closure;
use Exception;

class Router
{
    protected $filters   = [];
    protected $prefixes  = [];
    protected $prefix    = '';
    protected $befores   = [];
    protected $before    = [];
    protected $afters    = [];
    protected $after     = [];
    protected $callbacks = [];
    protected $routes    = [];


    /**
     * Add a route with a specific HTTP verb
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return $this
     */
    public function __call($method, $args)
    {
        $method = strtoupper($method);
        $verbs = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPITIONS', 'CONNECT', 'TRACE', 'ANY'];

        if (!in_array($method, $verbs)) {
            throw new Exception("Call to undefined method '{$method}'");
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

        $before = $before ? explode('|', $params['before']) : [];
        $after  = $after  ? explode('|', $params['after']) : [];

        $this->storeRoute($method, [
            'pattern'  => $pattern,
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
        }

        if ($before) {
            array_pop($this->before);
        }

        if ($after) {
            array_pop($this->after);
        }

        return $this;
    }


    /**
     * Get matching route
     *
     * @param  string $method
     * @param  string $path
     *
     * @return object
     */
    public function getMatch($method = null, $path = null)
    {
        $method = $method ?: $this->getRequestMethod();
        $path   = $path ?: $this->getRequestPath();

        foreach ($this->routes as $pattern => $methods) {
            preg_match(
                $this->regexifyPattern($pattern),
                $path,
                $matches
            );

            if ($matches) {
                $r = $this->getRoute($pattern, $method);

                if (!$r) {
                    throw new MethodNotAllowedException;
                }

                $r->method = strtoupper($method);
                $r->args   = $this->getMatchArgs($matches);

                return $r;
            }
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
     * @return mixed
     */
    public function dispatch($method = null, $path = null)
    {
        $match = $this->getMatch($method, $path);

        foreach ($match->before as $filter) {
            $response = $this->executeCallback($filter, $match->args, true);
            if (!is_null($response)) {
                return $response;
            }
        }

        $response = $this->executeCallback($match->callback, $match->args, true);
        if (!is_null($response)) {
            return $response;
        }

        foreach ($match->after as $filter) {
            $response = $this->executeCallback($filter, $match->args, true);
            if (!is_null($response)) {
                return $response;
            }
        }
    }


    /**
     * Get the requested HTTP method
     *
     * @return string
     */
    public function getRequestMethod()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            throw new Exception('No request method found');
        }

        return strtoupper($_SERVER['REQUEST_METHOD']);
    }


    /**
     * Get the requested URL path
     *
     * @return string
     */
    public function getRequestPath()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            throw new Exception('No request path found');
        }

        $path = trim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
        return "/" . $path;
    }


    /**
     * Execute a callback
     *
     * @param  mixed   $cb
     * @param  array   $args
     * @param  boolean $filter Set if the callback is a filter or not
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
            return call_user_func_array([new $cb[0], $cb[1]], $args);
        }

        if (is_string($cb) && strpos($cb, "::") !== false) {
            return call_user_func_array($cb, $args);
        }

        if ($filter) {
            if (!isset($this->filters[$cb])) {
                throw new \Exception("Undefined filter '{$cb}'");
            }

            return call_user_func_array($this->filters[$cb], $args);
        }

        throw new \Exception('Invalid callback');
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
        $from = ['\:alphanum\\', '\:alpha\\', '\:num\\', '\:any\\', '\?', '\(', '\)'];
        $to   = ['[a-zA-Z0-9]+', '[a-zA-Z]+', '[\-]?[\d\,\.]+', '[^\/]+', '?', '(', ')'];

        $pattern = str_replace('/(/', "(/", $pattern);
        echo "<pre>";
        var_dump($pattern);

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
    protected function getRoute($pattern, $method)
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

        foreach ($methods as $method) {
            $this->routes[$route['pattern']][strtoupper($method)] = $index;
        }
    }
}
