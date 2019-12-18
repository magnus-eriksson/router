<?php namespace Maer\Router;

use Closure;
use Exception;
use Maer\Router\RouteItem;
use Maer\Router\RouteCollection;
use Maer\Router\Exception\MethodNotAllowedException;
use Maer\Router\Exception\NotFoundException;
use Maer\Router\Exception\ControllerNotFoundException;

class Router implements RouterInterface
{
    /**
     * Http status codes
     */
    public const HTTP_FOUND              = 200;
    public const HTTP_NOT_FOUND          = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * Available settings
     */
    public const CONF_BASE_URL     = 'baseUrl';
    public const CONF_USE_BASE_URL = 'prependBaseUrl';

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var GroupCollection
     */
    protected $groups;

    /**
     * @var FilterCollection
     */
    protected $filters;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Custom callback resolver
     *
     * @var Closure
     */
    protected $resolver;

    /**
     * The last matched route
     *
     * @var null|RouteInfo
     */
    protected $lastMatchedRoute;


    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config  = new Config($config);
        $this->routes  = new RouteCollection($this->config);
        $this->groups  = new GroupCollection;
        $this->filters = new FilterCollection;

        $this->resolver = function ($callback) {
            return [
                new $callback[0],
                $callback[1]
            ];
        };
    }


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
    public function setConfig($name, $value = null): RouterInterface
    {
        $this->config->set($name, $value);

        return $this;
    }


    /**
     * Get a config parameter
     *
     * @param  string name
     *
     * @return mixed Returns null if the config isn't found
     */
    public function getConfig($name)
    {
        return $this->config->get($name);
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
    public function get(string $pattern, $callback, array $settings = []): RouterInterface
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
    public function put(string $pattern, $callback, array $settings = []): RouterInterface
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
    public function post(string $pattern, $callback, array $settings = []): RouterInterface
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
    public function delete(string $pattern, $callback, array $settings = []): RouterInterface
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
    public function add(string $method, string $pattern, $callback, array $settings = []): RouterInterface
    {
        $method   = $this->normalizeMethod($method);
        $pattern  = $this->normalizePath($pattern);
        $pattern  = $pattern ?: '/';

        $settings['before'] = $this->normalizeFilters($settings['before'] ?? []);

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
    public function group(array $groupInfo, Closure $routes): RouterInterface
    {
        $prefix = null;
        $before = [];

        if (isset($groupInfo['prefix']) && $groupInfo['prefix'] !== '') {
            $prefix = $this->normalizePath($groupInfo['prefix']);
        }

        if (isset($groupInfo['before']) && $groupInfo['before']) {
            $before = $this->normalizeFilters($groupInfo['before']);
        }

        $this->groups->push(new Group($prefix, $before));

        call_user_func_array($routes, [$this]);

        $this->groups->pop();

        return $this;
    }


    /**
     * Add a redirect
     *
     * @param  string  $from
     * @param  string  $to
     * @param  array   $params
     *
     * @return self
     */
    public function redirect(string $pattern, string $targetUrl, array $params = []): RouterInterface
    {
        $this->add('REDIRECT', $pattern, function () use ($targetUrl, $params) {
            $code = !empty($params['code'])
                ? $params['code']
                : 307;

            if (!empty($params['route'])) {
                $args = !empty($params['args'])
                    ? $params['args']
                    : [];

                $targetUrl = $this->getRoute($params['route'], is_array($args) ? $args : []);
            }

            header('location: ' . $targetUrl, true, $code);
            exit;
        }, $params);

        return $this;
    }


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
    public function redirectToRoute(string $name, array $args = [], int $httpStatusCode = 307, bool $useBaseUrl = false): RouterInterface
    {
        $targetUrl = $this->getRoute($name, $args, $useBaseUrl);

        header("location: {$to}", true, $httpStatusCode);
        exit;
    }


    /**
     * Add crud routes for a controller
     *
     * @param  string $pattern
     * @param  string $class
     * @param  array  $params
     *
     * @return self
     */
    public function crud(string $pattern, string $class, array $params = []): RouterInterface
    {
        $name = $params['name'] ?? null;

        $params['name'] = $name ? $name . '.create' : null;
        $this->post($pattern, "{$class}@create", $params);

        $params['name'] = $name ? $name . '.update' : null;
        $this->post("{$pattern}/(:any)", "{$class}@update", $params);

        $params['name'] = $name ? $name . '.one' : null;
        $this->get("{$pattern}/(:any)", "{$class}@one", $params);

        $params['name'] = $name ? $name . '.many' : null;
        $this->get("{$pattern}", "{$class}@many", $params);

        $params['name'] = $name ? $name . '.delete' : null;
        $this->delete("{$pattern}/(:any)", "{$class}@delete", $params);

        return $this;
    }


    /**
     * Add a custom callback resolver
     *
     * @param  Closure $resolver
     *
     * @return self
     */
    public function setCallbackResolver(Closure $resolver): RouterInterface
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
    public function addPlaceholder(string $key, string $regex): RouterInterface
    {
        $this->routes->addPlaceholder($key, $regex);

        return $this;
    }


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
    public function getRoute(string $name, array $args = [], bool $useBaseUrl = false): ?string
    {
        return $this->routes->getRoute($name, $args, $useBaseUrl);
    }


    /**
     * Get the last matched route
     *
     * @return RouteItem|null
     */
    public function getLastMatchedRoute(): ?RouteItem
    {
        return $this->lastMatchedRoute;
    }


    /**
     * Dispatch the router
     *
     * @param  string $method
     * @param  string $path
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
            return $this->triggerNotFound();
        } catch (MethodNotAllowedException $e) {
            return $this->triggerMethodNotAllowed();
        }

        // Save the route
        $this->lastMatchedRoute = $route;

        // Execute the before filters
        foreach ($route->getBeforeFilters() as $filter) {
            if (!$filterCallback = $this->filters->get($filter)) {
                throw new Exception("The route filter '{$filter}' was not found");
            }

            $filterResponse = $this->exec($filterCallback, [$route]);

            if ($filterResponse !== null) {
                return $filterResponse;
            }
        }

        return $this->exec($route->getCallback(), $route->getCallbackArguments());
    }


    /**
     * Add a new route filter
     *
     * @param  string $name
     * @param  mixed  $callback
     *
     * @return self
     */
    public function addFilter(string $name, $callback): RouterInterface
    {
        $this->filters->add($name, $callback);

        return $this;
    }


    /**
     * Add a callback for not found
     *
     * @param  string|Closure|array $callback
     *
     * @return self
     */
    public function onNotFound($callback): RouterInterface
    {
        $this->filters->setNotFoundCallback($callback);

        return $this;
    }


    /**
     * Add a callback for method not allowed
     *
     * @param  string|Closure|array $callback
     *
     * @return self
     */
    public function onMethodNotAllowed($callback): RouterInterface
    {
        $this->filters->setMethodNotAllowedCallback($callback);

        return $this;
    }


    /**
     * Trigger the "not found" (404) error
     *
     * @return mixed
     */
    public function triggerNotFound()
    {
        return $this->exec($this->filters->getNotFoundCallback());
    }


    /**
     * Trigger the "method not allowed" (405) error
     *
     * @return mixed
     */
    public function triggerMethodNotAllowed()
    {
        return $this->exec($this->filters->getMethodNotAllowedCallback());
    }


    /**
     * Get list of all registered routes
     *
     * @return array
     */
    public function getAllRoutes(): array
    {
        return $this->routes->getAllRoutes();
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

        if (is_array($callback) && count($callback) === 2) {
            if (is_string($callback[0]) && !class_exists($callback[0])) {
                throw new ControllerNotFoundException("The controller class '{$callback[0]}' was not found");
            }

            if ($this->resolver) {
                $resolver = $this->resolver;
                $callback = $resolver($callback);
            }
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
