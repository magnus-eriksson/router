<?php namespace Maer\Router;

use BadMethodCallException;
use Maer\Router\Exception\MethodNotAllowedException;
use Maer\Router\Exception\NotFoundException;

class RouteCollection
{
    public const REGEX_DELIMITER = '#';

    /**
     * @var RouteItem[]
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $names = [];

    /**
     * @var Config
     */
    protected $config;


    /**
     * Placeholders for dynamic route params
     *
     * @var array
     */
    protected $placeholders = [
        'any'      => '[^\/]+',
        'num'      => '[\d]+',
        'int'      => '[0-9]+',
        'alpha'    => '[a-zA-Z]+',
        'all'      => '.*',
        'alphanum' => '[a-zA-Z0-9]+',
        'word'     => '[\w]+',
    ];


    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }


    /**
     * Add a route to the collection
     *
     * @param RouteItem $route
     *
     * @return void
     */
    public function add(RouteItem $route)
    {
        $this->routes[$route->getPattern()][$route->getMethod()] = $route;

        if ($route->getName()) {
            $this->names[$route->getName()] = $route->getPattern();
        }
    }


    /**
     * Add a new placeholder
     *
     * @param string $key
     * @param string $regex
     *
     * @return self
     */
    public function addPlaceholder(string $key, string $regex): RouteCollection
    {
        $key = preg_quote("(:{$key})", self::REGEX_DELIMITER);

        $this->placeholders[$key] = $regex;

        return $this;
    }


    /**
     * Find a matching route
     *
     * @param  string $method
     * @param  string $path
     *
     * @return RouteItem
     *
     * @throws NotFoundException if there's no match on path
     * @throws MethodNotAllowedException if the path but not the method matches
     */
    public function findMatch(string $method, string $path)
    {
        $invalidMethod = false;

        $paramPlaceholders = array_keys($this->placeholders);
        $paramPlaceholders = array_map(function ($pattern) {
            return preg_quote($pattern, self::REGEX_DELIMITER);
        }, $paramPlaceholders);

        $paramPatterns = array_values($this->placeholders);

        $path = $path ?: '/';

        foreach ($this->routes as $pattern => $methods) {
            $pattern = $this->regexifyPattern($pattern);

            if (preg_match($pattern, $path, $match)) {
                if (empty($methods[$method])) {
                    $invalidMethod = true;
                    continue;
                }

                // Remove the first match since that's the complete string
                array_shift($match);

                // Remove any prepending or trailing slashes
                $match = array_map(function ($item) {
                    return trim($item, '/');
                }, $match);

                return $methods[$method]->setCallbackArguments($match);
            }
        }

        if ($invalidMethod) {
            throw new MethodNotAllowedException;
        }

        throw new NotFoundException;
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
    public function getRoute($name, array $args = [], bool $useBaseUrl = false): ?string
    {
        if (!isset($this->names[$name])) {
            return null;
        }

        $pattern = $this->names[$name];

        if (strpos($pattern, '(') === false) {
            // If we don't have any route parameters, just return the pattern
            // straight off. No need for any regex stuff.
            return '/' . trim($pattern, '/');
        }

        // Convert all placeholders to %o = optional and %r = required
        $from    = ['/(\([^\/]+[\)]+[\?])/', '/(\([^\/]+\))/'];
        $to      = ['%o', '%r'];
        $pattern = preg_replace($from, $to, $pattern);

        $frags = explode('/', trim($pattern, '/'));
        $url   = [];

        // Loop thru the pattern fragments and insert the arguments
        foreach ($frags as $frag) {
            if ($frag == '%r') {
                if (!$args) {
                    // A required parameter, but no more arguments.
                    throw new BadMethodCallException("Missing route parameters for route '{$name}'");
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

        $prefix     = '/';
        $baseUrl    = $this->config->get(Router::CONF_BASE_URL);
        $addBaseUrl = $useBaseUrl === true
            || $this->config->get(Router::CONF_USE_BASE_URL) === true;

        if ($baseUrl && $addBaseUrl) {
            $prefix = rtrim($baseUrl, '/') . '/';
        }

        return $prefix . implode('/', $url);
    }


    /**
     * Get all registered routs
     *
     * @return array
     */
    public function getAllRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Prepare the pattern for regex match (including resolving placeholders)
     *
     * @param  string $pattern
     *
     * @return string
     */
    protected function regexifyPattern(string $pattern): string
    {
        preg_match_all('/(\/?)\(:([^)]*)\)(\??)/', $pattern, $regExPatterns, PREG_SET_ORDER, 0);

        $pattern = preg_quote($pattern, self::REGEX_DELIMITER);

        foreach ($regExPatterns as $regexPattern) {
            $regex = $regexPattern[2] ?? null;

            if ($regex && !empty($this->placeholders[$regex])) {
                $replacement = sprintf(
                    '(%s%s)%s',
                    (empty($regexPattern[1]) ? '' : '\/'),
                    $this->placeholders[$regex],
                    ($regexPattern[3] ?? '')
                );

                $pattern  = str_replace(preg_quote($regexPattern[0], self::REGEX_DELIMITER), $replacement, $pattern);
            }
        }

        return self::REGEX_DELIMITER . "^{$pattern}$" . self::REGEX_DELIMITER;
    }
}
