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

    protected function checkSameRelease(SameReleasePolicy $sameReleasePolicy): bool
    {
        if (!$this->filterMatches($sameReleasePolicy->getFilter())) {
            return true;
        }

        $sourceInstallation = $this->copy->getSource()->getCurrentInstallation();
        $targetInstallation = $this->copy->getTarget()->getCurrentInstallation();

        if (null === $sourceInstallation) {
            return $this->preventExecution('source has no active installation.');
        }

        if (null === $targetInstallation) {
            return $this->preventExecution('target has no active installation.');
        }

        $sourceIdentification = $sourceInstallation->getCommitish();
        $targetIdentification = $targetInstallation->getCommitish();
        if ($sourceIdentification !== $targetIdentification) {
            return $this->preventExecution("source has a different version deployed as target. source: $sourceIdentification target: $targetIdentification.");
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function checkStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        if (!$this->filterMatches($stageWriteDownPolicy->getFilter())) {
            return true;
        }

        $targetStage = $this->copy->getTarget()->getStage();
        $sourceStage = $this->copy->getSource()->getStage();

        $stageIndex = $stageWriteDownPolicy->getLayerIndex($sourceStage);
        if (false === $stageIndex) {
            return $this->preventExecution("stage $targetStage not found in specified layers; policy undecidable.");
        }

        // if the stageIndex is the highest layer, we are allowed to write
        if ($stageWriteDownPolicy->isHighestLayer($stageIndex) || $stageWriteDownPolicy->isLowestLayer($stageIndex)) {
            return true;
        }

        // get the next lower layer & the current layer and check if the target is contained in there
        $stagesToCheck = array_merge($stageWriteDownPolicy->getNextLowerLayer($stageIndex), $stageWriteDownPolicy->getLayer($stageIndex));

        if (!in_array($targetStage, $stagesToCheck)) {
            return $this->preventExecution("target stage not within same or next lower stage as source stage. target stage $targetStage, source stage $sourceStage.");
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
