<?php namespace Maer\Router;

class Router
{
    protected $filters  = [];
    protected $prefixes = [];
    protected $prefix   = '';
    protected $routes   = [
        'get'     => [],
        'post'    => [],
        'put'     => [],
        'delete'  => [],
        'patch'   => [],
        'options' => [],
        'any'     => [],
    ];

    public function group(array $params, $callback)
    {
        $prefix = isset($params['prefix'])
            ? trim($params['prefix'], '/')
            : null;

        if ($prefix) {
            // Add the prefix to the list
            $this->prefixes[] = $prefix;
            $this->prefix = '/' . trim(implode('/', $this->prefixes), '/');
        }

        call_user_func_array($callback, [$this]);

        if ($prefix) {
            // Remove the prefix from the list
            array_pop($this->prefixes);
        }
    }

    public function add($method, $pattern, $callback)
    {
        $method  = strtolower($method);
        $pattern = '/' . trim($pattern, '/');

        if (!$this->isMethodValid($method)) {
            return false;
        }

        $pattern = rtrim($this->prefix . $pattern, '/');
        $pattern = $pattern ?: '/' . $pattern;

        $this->storeRoute([
            'pattern'  => $pattern,
            'method'   => $method,
            'callback' => $callback,
            'args'     => [],
        ]);
    }

    public function match($method, $path)
    {
        $method = strtolower($method);
        $path   = '/' . trim($path, '/');

        if (!$this->isMethodValid($method)) {
            return false;
        }

        // Check for direct match
        if (isset($this->routes[$method][$path])) {
            return $this->routes[$method][$path];
        };

        foreach (array_merge($this->routes['any'], $this->routes[$method]) as $pattern => $r) {
            $pattern = $this->regexifyPattern($pattern);
            preg_match($pattern, $path, $matches);

            if ($matches) {
                $r->args = $this->getMatchArgs($matches);
                return $r;
            }
        }

        return false;
    }

    protected function regexifyPattern($pattern)
    {
        $from = ['\:alphanum\\', '\:alpha\\', '\:num\\', '\?', '\(', '\)'];
        $to   = ['[a-zA-Z0-9]+', '[a-zA-Z]+', '[\-]?[\d\,\.]+', '?', '(', ')'];

        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace($from, $to, $pattern);

        return "/^$pattern$/";
    }

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

    protected function isMethodValid($method)
    {
        return array_key_exists(strtolower($method), $this->routes);
    }

    protected function storeRoute(array $route)
    {
        $this->routes[$route['method']][$route['pattern']] = (object) $route;
    }
}
