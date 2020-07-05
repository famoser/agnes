<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

class Action
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string[]
     */
    private $arguments;

    use FilterTrait;

    /**
     * Action constructor.
     *
     * @param string[] $arguments
     */
    public function __construct(string $name, string $action, array $arguments, ?Filter $filter)
    {
        $this->name = $name;
        $this->action = $action;
        $this->arguments = $arguments;
        $this->filter = $filter;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
