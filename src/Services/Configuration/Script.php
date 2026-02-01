<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

readonly class Script
{
    /**
     * @param string[] $script
     */
    public function __construct(
        private string $name,
        private array $script,
        private ?Filter $filter
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getScript(): array
    {
        return $this->script;
    }

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }
}
