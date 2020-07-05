<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

class Script
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $script;

    use FilterTrait;

    /**
     * Script constructor.
     */
    public function __construct(string $name, array $script, ?Filter $filter)
    {
        $this->name = $name;
        $this->script = $script;
        $this->filter = $filter;
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
}
