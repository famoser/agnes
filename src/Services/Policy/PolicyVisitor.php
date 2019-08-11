<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Filter;
use Agnes\Models\Policies\Policy;
use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Agnes\Models\Policies\SameReleasePolicy;
use Agnes\Models\Policies\StageWriteDownPolicy;
use Agnes\Models\Policies\StageWriteUpPolicy;
use Exception;

abstract class PolicyVisitor
{
    /**
     * @param StageWriteUpPolicy $stageWriteUpPolicy
     * @return bool
     * @throws Exception
     */
    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        return $this->visitDefault($stageWriteUpPolicy);
    }

    /**
     * @param StageWriteDownPolicy $stageWriteDownPolicy
     * @return bool
     * @throws Exception
     */
    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        return $this->visitDefault($stageWriteDownPolicy);
    }

    /**
     * @param ReleaseWhitelistPolicy $releaseWhitelistPolicy
     * @return bool
     * @throws Exception
     */
    public function visitReleaseWhitelist(ReleaseWhitelistPolicy $releaseWhitelistPolicy): bool
    {
        return $this->visitDefault($releaseWhitelistPolicy);
    }

    /**
     * @param SameReleasePolicy $sameReleasePolicy
     * @return bool
     * @throws Exception
     */
    public function visitSameRelease(SameReleasePolicy $sameReleasePolicy): bool
    {
        return $this->visitDefault($sameReleasePolicy);
    }

    /**
     * @param Policy $policy
     * @return bool
     */
    public function isApplicable(Policy $policy): bool
    {
        return $this->filterApplies($policy->getFilter());
    }

    /**
     * checks if the policy has to be checked for
     *
     * @param Filter $filter
     * @return bool
     */
    protected abstract function filterApplies(?Filter $filter);

    /**
     * @param Policy $stageWriteDownPolicy
     * @return bool
     * @throws Exception
     */
    private function visitDefault(Policy $stageWriteDownPolicy): bool
    {
        throw new Exception("The policy " . get_class($stageWriteDownPolicy) . " has not been implemented for the executing task.");
    }
}