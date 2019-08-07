<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Policies\EnvironmentWriteDownPolicy;
use Agnes\Models\Policies\EnvironmentWriteUpPolicy;
use Agnes\Models\Policies\Policy;
use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Agnes\Models\Tasks\Filter;

abstract class PolicyVisitor
{
    /**
     * @param EnvironmentWriteUpPolicy $environmentWriteUpPolicy
     * @return bool
     * @throws \Exception
     */
    public function visitEnvironmentWriteUp(EnvironmentWriteUpPolicy $environmentWriteUpPolicy): bool
    {
        throw new \Exception("This policy has not been implemented for the task at hand.");
    }

    /**
     * @param EnvironmentWriteDownPolicy $environmentWriteDownPolicy
     * @return bool
     * @throws \Exception
     */
    public function visitEnvironmentWriteDown(EnvironmentWriteDownPolicy $environmentWriteDownPolicy): bool
    {
        throw new \Exception("This policy has not been implemented for the task at hand.");
    }

    /**
     * @param ReleaseWhitelistPolicy $releaseWhitelistPolicy
     * @return bool
     * @throws \Exception
     */
    public function visitReleaseWhitelist(ReleaseWhitelistPolicy $releaseWhitelistPolicy): bool
    {
        throw new \Exception("This policy has not been implemented for the task at hand.");
    }

    /**
     * @param Policy $policy
     * @return bool
     */
    public function isApplicable(Policy $policy)
    {
        return $this->filterApplies($policy->getFilter());
    }

    /**
     * checks if the policy has to be checked for
     *
     * @param Filter $policy
     * @return bool
     */
    protected abstract function filterApplies(?Filter $policy);
}