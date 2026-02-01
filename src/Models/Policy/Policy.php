<?php

namespace Agnes\Models\Policy;

use Agnes\Models\Filter;
use Agnes\Services\Policy\AbstractPolicyVisitor;

abstract readonly class Policy
{
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
