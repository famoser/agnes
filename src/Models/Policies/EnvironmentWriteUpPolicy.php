<?php


namespace Agnes\Models\Policies;


use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\PolicyVisitor;

class EnvironmentWriteUpPolicy extends LayeredPolicy
{
    /**
     * @param PolicyVisitor $visitor
     * @return bool
     */
    public function accept(PolicyVisitor $visitor)
    {
        return $visitor->visit($this);
    }
}