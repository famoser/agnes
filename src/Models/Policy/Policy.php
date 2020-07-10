<?php

namespace Agnes\Models\Policy;

use Agnes\Models\Filter;
use Agnes\Services\Policy\AbstractPolicyVisitor;
use Exception;

abstract class Policy
{
    /**
     * @var Filter|null
     */
    private $filter;

    /**
     * Policy constructor.
     */
    public function __construct(?Filter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    abstract public function accept(AbstractPolicyVisitor $visitor);

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }
}
