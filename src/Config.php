<?php namespace Maer\Router;

use InvalidArgumentException;

class Config
{
    /**
     * @var array
     */
    protected $config = [
        Router::CONF_BASE_URL     => null,
        Router::CONF_USE_BASE_URL => false,
    ];


    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_replace_recursive($this->config, $config);
    }


    /**
     * Set a config value
     *
     * @param string|array $name
     * @param mixed        $value
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            $this->config = array_replace_recursive($this->config, $name);

            return $this;
        }

        if (is_string($name)) {
            $this->config[$name] = $value;

            return $this;
        }

        throw InvalidArgumentException("The first argument to setConfig must be a string or an array. Got " . gettype($name));
    }


    /**
     * Get a config value
     *
     * @param  string $name
     *
     * @return mixed Returns null if not set
     */
    public function get(string $name)
    {
        return $this->config[$name] ?? null;
    }
}
