<?php namespace Maer\Router;

use Closure;
use Maer\Router\RouteItem;
use Maer\Router\RouteCollection;
use Maer\Router\Exception\MethodNotAllowedException;
use Maer\Router\Exception\NotFoundException;

class Router
{
    /**
     * Http status codes
     */
    const HTTP_FOUND              = 200;
    const HTTP_NOT_FOUND          = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var GroupCollection
     */
    protected $groups;

    /**
     * Custom callback resolver
     *
     * @var Closure
     */
    protected $resolver;


    public function __construct()
    {
        $this->routes = new RouteCollection;
        $this->groups = new GroupCollection;
    }


    /**
     * Add a GET route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function get(string $pattern, $callback, array $settings = []): Router
    {
        return $this->add('GET', $pattern, $callback, $settings);
    }


    /**
     * Add a PUT route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function put(string $pattern, $callback, array $settings = []): Router
    {
        return $this->add('PUT', $pattern, $callback, $settings);
    }


    /**
     * Add a POST route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function post(string $pattern, $callback, array $settings = []): Router
    {
        return $this->add('POST', $pattern, $callback, $settings);
    }


    /**
     * Add a DELETE route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function delete(string $pattern, $callback, array $settings = []): Router
    {
        return $this->add('DELETE', $pattern, $callback, $settings);
    }


    /**
     * Add a route
     *
     * @param  string $method
     * @param  string $pattern
     * @param  mixed  $callback
     *
     * @return self
     */
    public function add(string $method, string $pattern, $callback, array $settings = []): Router
    {
        $method   = $this->normalizeMethod($method);
        $pattern  = $this->normalizePath($pattern);
        $pattern  = $pattern ?: '/';

        $settings['before'] = $this->normalizeFilters($settings['before'] ?? []);
        $settings['after']  = $this->normalizeFilters($settings['after'] ?? []);

        [$pattern, $settings] = $this->groups->decorate($pattern, $settings);

        $this->routes->add(new RouteItem($method, $pattern, $callback, $settings));

        return $this;
    }


    /**
     * Add a route group
     *
     * @param  array   $groupInfo
     * @param  Closure $routes
     *
     * @return self
     */
    public function group(array $groupInfo, Closure $routes): Router
    {
        $prefix = null;
        $before = [];
        $after  = [];

        if (isset($groupInfo['prefix']) && $groupInfo['prefix'] !== '') {
            $prefix = $this->normalizePath($groupInfo['prefix']);
        }

        if (isset($groupInfo['before']) && $groupInfo['before']) {
            $before = $this->normalizeFilters($groupInfo['before']);
        }

        if (isset($groupInfo['after']) && $groupInfo['after']) {
            $after = $this->normalizeFilters($groupInfo['after']);
        }

        $this->groups->push(new Group($prefix, $before, $after));

        call_user_func_array($routes, [$this]);

        $this->groups->pop();

        return $this;
    }


    /**
     * Add a custom callback resolver
     *
     * @param  Closure $resolver
     *
     * @return self
     */
    public function setCallbackResolver(Closure $resolver): Router
    {
        $this->resolver = $resolver;

        return $this;
    }


    /**
     * Add a custom placeholder
     *
     * @param  string $key
     * @param  string $regex
     *
     * @return self
     */
    public function addPlaceholder(string $key, string $regex): Router
    {
        $this->routes->addPlaceholder($key, $regex);

        return $this;
    }


    /**
     * Get a named route
     *
     * @return string|null
     */
    public function getRoute(string $name, array $args = []): ?string
    {
        return $this->routes->getRoute($name, $args);
    }


    /**
     * Dispatch the router
     *
     * @return mixed
     */
    public function dispatch(?string $method = null, ?string $path = null)
    {
        if (!$method) {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        if (!$path) {
            $path = $_SERVER['REQUEST_URI'] ?? '';
        }

        $method = $this->normalizeMethod($method);
        $path   = $this->normalizePath(parse_url($path, PHP_URL_PATH));

        try {
            $route = $this->routes->findMatch($method, $path);
        } catch (NotFoundException $e) {
            die('404 - Not found');
        } catch (MethodNotAllowedException $e) {
            die('405 - Method Not Allowed');
        }

        return $this->exec($route->getCallback(), $route->getCallbackArguments());
    }


    /**
     * Execute callback
     *
     * @param  mixed $callback
     * @param  array $args
     *
     * @return mixed
     */
    protected function exec($callback, array $args = [])
    {
        if (is_string($callback) && strpos($callback, '@') > 0) {
            $callback = explode('@', $callback, 2);
        }

        if ($this->resolver && is_array($callback) && count($callback) === 2) {
            $resolver = $this->resolver;
            $callback = $resolver($callback);
        }

        return call_user_func_array($callback, $args);
    }


    /**
     * Normalize the method string
     *
     * @param  string $method
     *
     * @return string
     */
    protected function normalizeMethod(string $method): string
    {
        return strtoupper($method);
    }


    /**
     * Normalize the path string
     *
     * @param  string $path
     *
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        return trim($path, '/');
    }


    /**
     * Normalize filters
     *
     * @param  mixed $filters
     *
     * @return array
     */
    protected function normalizeFilters($filters): array
    {
        if (is_array($filters)) {
            return $filters;
        }

        if (is_string($filters)) {
            return array_filter(explode('|', $filters), function ($value) {
                return is_string($value) && $value !== '';
            });
        }

        return [];
    }
}
