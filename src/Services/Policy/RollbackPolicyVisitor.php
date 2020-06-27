<?php

namespace Agnes\Services\Policy;

use Agnes\Actions\Rollback;
use Agnes\Models\Filter;

class RollbackPolicyVisitor extends PolicyVisitor
{
    /**
     * @var Rollback
     */
    private $rollback;

    /**
     * RollbackVisitor constructor.
     */
    public function __construct(Rollback $rollback)
    {
        $this->rollback = $rollback;
    }

    /**
     * checks if the policy has to be checked for.
     *
     * @param Filter $filter
     *
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return null === $filter || $filter->instanceMatches($this->rollback->getInstance());
    }
}
