<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

class Task
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $task;

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
    public function __construct(string $name, string $task, array $arguments, ?Filter $filter)
    {
        $this->name = $name;
        $this->task = $task;
        $this->arguments = $arguments;
        $this->filter = $filter;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getTask(): string
    {
        return $this->task;
    }
}
