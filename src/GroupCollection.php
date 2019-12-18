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
     * Add a group
     *
     * @param  Group  $group
     *
     * @return void
     */
    public function push(Group $group)
    {
        $this->groups[] = $group;
    }


    /**
     * Remove the last group from the stack
     *
     * @return void
     */
    public function pop()
    {
        if ($this->groups) {
            array_pop($this->groups);
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

        if ($this->before) {
            $settings['before'] = array_merge($this->before, $settings['before']);
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

        foreach ($this->groups as $group) {
            if ($group->getPrefix()) {
                $this->prefixes[] = $group->getPrefix();
            }

            if ($group->getBeforeFilters()) {
                $this->before = array_merge($this->before, $group->getBeforeFilters());
            }
        }
    }
}
