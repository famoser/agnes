<?php


namespace Agnes\Services\Configuration;


class Environment
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $stages;

    /**
     * Environment constructor.
     * @param string $name
     * @param string[] $stages
     */
    public function __construct(string $name, array $stages)
    {
        $this->name = $name;
        $this->stages = $stages;
    }

    /**
     * @return string
     */
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