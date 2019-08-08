<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Tasks\Filter;
use Agnes\Services\CopyShared\CopyShared;

class CopySharedPolicyVisitor extends PolicyVisitor
{
    /**
     * @var CopyShared
     */
    private $copyShared;

    /**
     * CopySharedPolicyVisitor constructor.
     * @param CopyShared $copyShared
     */
    public function __construct(CopyShared $copyShared)
    {
        $this->copyShared = $copyShared;
    }

    /**
     * checks if the policy has to be checked for
     *
     * @param Filter $filter
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return $filter === null ||
            $filter->instanceMatches($this->copyShared->getSource()) ||
            $filter->instanceMatches($this->copyShared->getTarget());
    }
}