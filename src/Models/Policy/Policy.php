<?php

namespace Agnes\Models\Policy;

use Agnes\Models\Filter;
use Agnes\Services\Policy\AbstractPolicyVisitor;

abstract class Policy
{
    /**
     * Policy constructor.
     */
    public function __construct(private string $name, private ?Filter $filter)
    {
    }

    /**
     * @return bool
     *
     *
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
