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
     * @param Rollback $rollback
     */
    public function __construct(Rollback $rollback)
    {
        $this->rollback = $rollback;
    }

    /**
     * checks if the policy has to be checked for
     *
     * @param Filter $filter
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return $filter === null || $filter->instanceMatches($this->rollback->getInstance());
    }
}