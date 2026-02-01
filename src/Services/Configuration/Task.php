<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

class Task
{
    use FilterTrait;

    private string $name;

    private string $task;

    /**
     * @var string[]
     */
    private array $arguments;

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
