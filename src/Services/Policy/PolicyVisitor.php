<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Policies\EnvironmentWriteUpPolicy;

interface PolicyVisitor
{
    /**
     * @param EnvironmentWriteUpPolicy $environmentWriteUpPolicy
     * @return bool
     */
    public function visit(EnvironmentWriteUpPolicy $environmentWriteUpPolicy);
}