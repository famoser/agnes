<?php

namespace Agnes\Services\Configuration;

readonly class Environment
{
    /**
     * @param string[] $stages
     */
    public function __construct(private string $name, private array $stages)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getStages(): array
    {
        return $this->stages;
    }
}
