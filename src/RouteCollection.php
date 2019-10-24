<?php namespace Maer\Router;

use Maer\Router\Exception\MethodNotAllowedException;
use Maer\Router\Exception\NotFoundException;

class RouteCollection
{
    /**
     * @var RouteItem[]
     */
    protected $items = [];

    /**
     * @var array
     */
    protected $names = [];


    /**
     * Placeholders for dynamic route params
     *
     * @var array
     */
    protected $placeholders = [
        '(:any)'      => '([^\/]+)',
        '(:num)'      => '([\d]+)',
        '(:int)'      => '([0-9]+)',
        '(:alpha)'    => '([a-zA-Z]+)',
        '(:all)'      => '(.*)',
        '(:aplhanum)' => '([a-zA-Z0-9]+)',
        '(:word)'     => '([\w]+)',
    ];


    /**
     * Add a route to the collection
     *
     * @param RouteItem $route
     *
     * @return void
     */
    public function add(RouteItem $route)
    {
        $this->items[$route->getPattern()][$route->getMethod()] = $route;

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
        $key = preg_quote("(:{$key})", '#');

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
            return preg_quote($pattern, '#');
        }, $paramPlaceholders);

        $paramPatterns     = array_values($this->placeholders);

        $path = $path ?: '/';

        foreach ($this->items as $pattern => $methods) {
            $pattern = preg_quote($pattern, '#');
            $pattern = str_replace($paramPlaceholders, $paramPatterns, $pattern);

            if (preg_match("#^{$pattern}$#", $path, $match)) {
                if (empty($methods[$method])) {
                    $invalidMethod = true;
                    continue;
                }

                // Remove the first match since that's the complete string
                array_shift($match);

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
     * @return string|null
     */
    public function getRoute(string $name, array $args = []): ?string
    {
        if (!isset($this->names[$name])) {
            return null;
        }

        $pattern  = $this->names[$name];
        $uri      = [];

        $argIndex = 0;
        foreach (explode('/', $pattern) as $segment) {
            if (!isset($this->placeholders[$segment])) {
                $uri[] = $segment;
                continue;
            }

        }

        var_dump($pattern, $this->names);
        exit;
    }
}
