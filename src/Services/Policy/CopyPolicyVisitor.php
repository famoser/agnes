<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Filter;
use Agnes\Models\Policy\SameReleasePolicy;
use Agnes\Models\Policy\StageWriteDownPolicy;
use Agnes\Models\Task\Copy;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

class CopyPolicyVisitor extends NoPolicyVisitor
{
    /**
     * @var Copy
     */
    private $copy;

    /**
     * CopyPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, Copy $copy)
    {
        parent::__construct($io, $copy);

        $this->copy = $copy;
    }

    protected function checkSameRelease(SameReleasePolicy $policy): bool
    {
        if (!$this->filterMatches($policy->getFilter())) {
            return true;
        }

        $sourceInstallation = $this->copy->getSource()->getCurrentInstallation();
        $targetInstallation = $this->copy->getTarget()->getCurrentInstallation();

        if (null === $sourceInstallation) {
            return $this->policyPreventsExecution($policy, 'source has no active installation.');
        }

        if (null === $targetInstallation) {
            return $this->policyPreventsExecution($policy, 'target has no active installation.');
        }

        $sourceReleaseOrHash = $sourceInstallation->getReleaseOrHash();
        $targetReleaseOrHash = $targetInstallation->getReleaseOrHash();
        if ($sourceReleaseOrHash !== $targetReleaseOrHash) {
            return $this->policyPreventsExecution($policy, "source has a different release or hash as the active installation as target. source: $sourceReleaseOrHash target: $targetReleaseOrHash.");
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function checkStageWriteDown(StageWriteDownPolicy $policy): bool
    {
        if (!$this->filterMatches($policy->getFilter())) {
            return true;
        }

        $targetStage = $this->copy->getTarget()->getStage();
        $sourceStage = $this->copy->getSource()->getStage();

        $stageIndex = $policy->getLayerIndex($sourceStage);
        if (false === $stageIndex) {
            return $this->policyPreventsExecution($policy, "stage $targetStage not found in specified layers; policy undecidable.");
        }

        // if the stageIndex is the highest layer, we are allowed to write
        if ($policy->isHighestLayer($stageIndex) || $policy->isLowestLayer($stageIndex)) {
            return true;
        }

        // get the next lower layer & the current layer and check if the target is contained in there
        $stagesToCheck = array_merge($policy->getNextLowerLayer($stageIndex), $policy->getLayer($stageIndex));

        if (!in_array($targetStage, $stagesToCheck)) {
            return $this->policyPreventsExecution($policy, "target stage not within same or next lower stage as source stage. target stage $targetStage, source stage $sourceStage.");
        }

        return true;
    }

    /**
     * checks if the policy has to be checked for.
     *
     * @param Filter $filter
     */
    protected function filterMatches(?Filter $filter): bool
    {
        return null === $filter ||
            $filter->instanceMatches($this->copy->getSource()) ||
            $filter->instanceMatches($this->copy->getTarget());
    }
}
