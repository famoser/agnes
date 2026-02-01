<?php

namespace Agnes\Services\Configuration;

class Environment
{
    /**
     * Environment constructor.
     *
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
