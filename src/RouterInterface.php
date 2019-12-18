<?php namespace Maer\Router;

use Closure;
use Exception;

interface RouterInterface
{
    /**
     * Set a config parameter
     *
     * @param  string|array $name
     * @param  mixed        $value
     *
     * @return self
     *
     * @throws InvalidArgumentException if the first arg isn't an array or string
     */
    public function setConfig($name, $value = null): RouterInterface;


    /**
     * Get a config parameter
     *
     * @param  string name
     *
     * @return mixed Returns null if the config isn't found
     */
    public function getConfig($name);


    /**
     * Add a GET route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function get(string $pattern, $callback, array $settings = []): RouterInterface;


    /**
     * Add a PUT route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function put(string $pattern, $callback, array $settings = []): RouterInterface;


    /**
     * Add a POST route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function post(string $pattern, $callback, array $settings = []): RouterInterface;


    /**
     * Add a DELETE route
     *
     * @param  string $pattern
     * @param  mixed  $callback
     * @param  array  $settings
     *
     * @return self
     */
    public function delete(string $pattern, $callback, array $settings = []): RouterInterface;


    /**
     * Add a route
     *
     * @param  string $method
     * @param  string $pattern
     * @param  mixed  $callback
     *
     * @return self
     */
    public function add(string $method, string $pattern, $callback, array $settings = []): RouterInterface;


    /**
     * Add a route group
     *
     * @param  array   $groupInfo
     * @param  Closure $routes
     *
     * @return self
     */
    public function group(array $groupInfo, Closure $routes): RouterInterface;


    /**
     * Add a redirect
     *
     * @param  string  $from
     * @param  string  $to
     * @param  array   $params
     *
     * @return self
     */
    public function redirect(string $from, string $to, array $params = []): RouterInterface;


    /**
     * Redirect to a route
     *
     * @param  string  $name
     * @param  array   $args
     * @param  int     $httpStatusCode
     * @param  bool    $useBaseUrl
     *
     * @return self
     */
    public function redirectToRoute(string $name, array $args = [], int $httpStatusCode = 307, bool $useBaseUrl = false): RouterInterface;


    /**
     * Add crud routes for a controller
     *
     * @param  string $pattern
     * @param  string $callback
     * @param  array  $params
     *
     * @return self
     */
    public function crud(string $pattern, string $class, array $params = []): RouterInterface;


    /**
     * Add a new route filter
     *
     * @param  string $name
     * @param  mixed  $callback
     *
     * @return self
     */
    public function addFilter(string $name, $callback): RouterInterface;


    /**
     * Add a callback for not found
     *
     * @param  string|Closure|array $callback
     *
     * @return self
     */
    public function onNotFound($callback): RouterInterface;


    /**
     * Add a callback for method not allowed
     *
     * @param  string|Closure|array $callback
     *
     * @return self
     */
    public function onMethodNotAllowed($callback): RouterInterface;


    /**
     * Trigger the "not found" (404) error
     *
     * @return mixed
     */
    public function triggerNotFound();


    /**
     * Trigger the "method not allowed" (405) error
     *
     * @return mixed
     */
    public function triggerMethodNotAllowed();


    /**
     * Dispatch the router
     *
     * @param  string $method
     * @param  string $path
     *
     * @return mixed
     */
    public function dispatch(?string $method = null, ?string $path = null);


    /**
     * Add a custom callback resolver
     *
     * @param  Closure $resolver
     *
     * @return self
     */
    public function setCallbackResolver(Closure $resolver): RouterInterface;


    /**
     * Add a custom placeholder
     *
     * @param  string $key
     * @param  string $regex
     *
     * @return self
     */
    public function addPlaceholder(string $key, string $regex): RouterInterface;


    /**
     * Get a named route
     *
     * @param  string $name
     * @param  array  $args
     * @param  bool   $useBaseUrl
     *
     * @return string|null
     *
     * @throws Exception If there aren't enough arguments for all required parameters
     */
    public function getRoute(string $name, array $args = [], bool $useBaseUrl = false): ?string;


    /**
     * Get the last matched route
     *
     * @return RouteItem|null
     */
    public function getLastMatchedRoute(): ?RouteItem;
}
