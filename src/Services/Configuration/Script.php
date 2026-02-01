<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

class Script
{
    use FilterTrait;

    /**
     * Script constructor.
     */
    public function __construct(private string $name, /**
     * @var string[]
     */
    private array $script, ?Filter $filter)
    {
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
