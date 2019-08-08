<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Policies\Policy;
use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Agnes\Models\Policies\StageWriteDownPolicy;
use Agnes\Models\Policies\StageWriteUpPolicy;
use Agnes\Models\Tasks\Filter;

abstract class PolicyVisitor
{
    /**
     * @param StageWriteUpPolicy $stageWriteUpPolicy
     * @return bool
     * @throws \Exception
     */
    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        throw new \Exception("This policy has not been implemented for the task at hand.");
    }

    /**
     * @param StageWriteDownPolicy $stageWriteDownPolicy
     * @return bool
     * @throws \Exception
     */
    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
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