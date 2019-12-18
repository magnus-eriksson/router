<?php namespace Maer\Router;

class Group
{
    /**
     * @var string|null
     */
    protected $prefix;

    /**
     * @var array
     */
    protected $before = [];


    /**
     * @param string|null $prefix
     * @param array       $before
     * @param array       $after
     */
    public function __construct(?string $prefix = null, array $before = [])
    {
        $this->prefix = $prefix;
        $this->before = $before;
    }


    /**
     * Get the group prefix
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
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
}
