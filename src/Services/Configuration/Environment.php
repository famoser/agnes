<?php

namespace Agnes\Services\Configuration;

class Environment
{
    private string $name;

    /**
     * @var string[]
     */
    private array $stages;

    /**
     * Environment constructor.
     *
     * @param string[] $stages
     */
    public function __construct(string $name, array $stages)
    {
        $this->name = $name;
        $this->stages = $stages;
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
