<?php

namespace Agnes\Models\Policy;

use Agnes\Models\Filter;
use Agnes\Services\Policy\AbstractPolicyVisitor;
use Exception;

abstract class Policy
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Filter|null
     */
    private $filter;

    /**
     * Policy constructor.
     */
    public function __construct(string $name, ?Filter $filter)
    {
        $this->name = $name;
        $this->filter = $filter;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    abstract public function accept(AbstractPolicyVisitor $visitor);

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }
}
