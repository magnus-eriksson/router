<?php namespace Maer\Router;

class GroupCollection
{
    /**
     * @var Group[]
     */
    protected $groups = [];

    /**
     * All prefixes
     *
     * @var array
     */
    protected $prefixes = [];

    /**
     * Before filters for all current groups
     *
     * @var array
     */
    protected $before = [];

    /**
     * After filter for all current groups
     *
     * @var array
     */
    protected $after  = [];


    /**
     * Add a group
     *
     * @param  Group  $group
     *
     * @return void
     */
    public function push(Group $group)
    {
        //$this->groups[] = $group;
    }


    /**
     * Remove the last group from the stack
     *
     * @return void
     */
    public function pop()
    {
        if ($this->groups) {
            $this->groups = array_pop($this->groups);
        }
    }


    /**
     * Decorate pattern and settings with group data
     *
     * @param  string $pattern
     * @param  array  $settings
     *
     * @return array
     */
    public function decorate(string $pattern, array $settings): array
    {
        $this->buildGroupData();

        $prefix = implode('/', $this->prefixes);

        if ($prefix) {
            // Prepend the prefix and normalize the result
            $pattern = trim($prefix . '/' . ltrim($pattern, '/'), '/');
        }

        // Filters
        foreach (['before', 'after'] as $type) {
            if ($this->{$type}) {
                $settings[$type] = array_merge($this->{$type}, $settings[$type]);
            }
        }

        return [$pattern, $settings];
    }


    /**
     * Generate current group info
     *
     * @return void
     */
    protected function buildGroupData()
    {
        $this->prefixes = [];
        $this->before   = [];
        $this->after    = [];

        foreach ($this->groups as $group) {
            if ($group->getPrefix()) {
                $this->prefixes[] = $group->getPrefix();
            }

            if ($group->getBeforeFilters()) {
                $this->before = array_merge($this->before, $group->getBeforeFilters());
            }

            if ($group->getAfterFilters()) {
                $this->after = array_merge($this->after, $group->getAfterFilters());
            }
        }
    }
}
