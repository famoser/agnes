<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

class Task
{
    /**
     * @param string[] $arguments
     */
    public function __construct(private string $name, private string $task, private array $arguments, private ?Filter $filter)
    {
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

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }
}
