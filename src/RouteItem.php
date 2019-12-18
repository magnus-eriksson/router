<?php namespace Maer\Router;

class RouteItem
{
    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var string
     */
    protected $callback;

    /**
     * @var null|string
     */
    protected $name;

    /**
     * Before filters
     *
     * @var array
     */
    protected $before = [];

    /**
     * Injected route arguments when route is a match
     *
     * @var array
     */
    protected $arguments = [];


    /**
     * @param string $method
     * @param string $pattern
     * @param mixed  $callback
     * @param array  $settings
     */
    public function __construct(string $method, string $pattern, &$callback, array $settings = [])
    {
        // Base info
        $this->method   = $method;
        $this->pattern  = $pattern;
        $this->callback = $callback;

        // Extra settings
        $this->name   = $settings['name'] ?? null;
        $this->before = $settings['before'] ?? [];
    }


    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }


    /**
     * Get the pattern
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }


    /**
     * Get the callback
     *
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }


    /**
     * Get the route name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }


    /**
     * Get the before filters
     *
     * @return array
     */
    public function getBeforeFilters(): array
    {
        return $this->before;
    }


    /**
     * Set the route arguments to be used when the callback is executed
     *
     * @param array $arguments
     *
     * @return RouteItem
     */
    public function setCallbackArguments(array $arguments): RouteItem
    {
        $this->arguments = $arguments;

        return $this;
    }


    /**
     * Get the route arguments to be used when the callback is executed
     *
     * @return array
     */
    public function getCallbackArguments(): array
    {
        return $this->arguments;
    }
}
