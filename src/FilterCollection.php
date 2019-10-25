<?php namespace Maer\Router;

class FilterCollection
{
    /**
     * @var array
     */
    protected $filters = [];

    /**
     * Callback for not found
     *
     * @var mixed
     */
    protected $onNotFound;

    /**
     * Callback for method not allowed
     *
     * @var mixed
     */
    protected $onMethodNotAllowed;


    /**
     * Add a filter
     *
     * @param string $name
     * @param mixed  $callback
     *
     * @return FilterCollection
     */
    public function add(string $name, $callback): FilterCollection
    {
        $this->filters[$name] = $callback;

        return $this;
    }


    /**
     * Set callback for not found (404)
     *
     * @param  mixed $callback
     *
     * @return FilterCollection
     */
    public function setNotFoundCallback($callback): FilterCollection
    {
        $this->onNotFound = $callback;

        return $this;
    }


    /**
     * Set callback for method not allowed (405)
     *
     * @param  mixed $callback
     *
     * @return FilterCollection
     */
    public function setMethodNotAllowedCallback($callback): FilterCollection
    {
        $this->onMethodNotAllowed = $callback;

        return $this;
    }


    /**
     * Get callback for not found (404)
     *
     * @return mixed
     */
    public function getNotFoundCallback()
    {
        return $this->onNotFound ?? function () {
            http_response_code(404);

            return '404 - Page not found - Collection';
        };
    }


    /**
     * Get callback for not found (404)
     *
     * @return mixed
     */
    public function getMethodNotAllowedCallback()
    {
        return $this->onMethodNotAllowed ?? function () {
            http_response_code(405);

            return '405 - Method not allowed - Collection';
        };
    }


    /**
     * Check if a filter exist
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->filters);
    }


    /**
     * Get a filter
     *
     * @param  string $name
     *
     * @return mixed Null if the filter wasn't found
     */
    public function get(string $name)
    {
        return $this->filters[$name] ?? null;
    }
}
