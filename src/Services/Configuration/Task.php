<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

class Task
{
    use FilterTrait;
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
