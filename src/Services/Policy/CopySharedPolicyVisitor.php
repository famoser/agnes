<?php

namespace Agnes\Services\Policy;

use Agnes\Actions\CopyShared;
use Agnes\Models\Filter;
use Agnes\Models\Policies\SameReleasePolicy;
use Agnes\Models\Policies\StageWriteDownPolicy;
use Exception;

class CopySharedPolicyVisitor extends PolicyVisitor
{
    /**
     * @var CopyShared
     */
    private $copyShared;

    /**
     * CopySharedPolicyVisitor constructor.
     */
    public function __construct(CopyShared $copyShared)
    {
        $this->copyShared = $copyShared;
    }

    public function visitSameRelease(SameReleasePolicy $sameReleasePolicy): bool
    {
        $sourceRelease = $this->copyShared->getSource()->getCurrentReleaseName();
        $targetRelease = $this->copyShared->getTarget()->getCurrentReleaseName();

        return null !== $sourceRelease && $sourceRelease === $targetRelease;
    }

    /**
     * @throws Exception
     */
    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        $targetStage = $this->copyShared->getTarget()->getStage();
        $sourceStage = $this->copyShared->getSource()->getStage();

        $stageIndex = $stageWriteDownPolicy->getLayerIndex($sourceStage);
        if (false === $stageIndex) {
            throw new Exception('Stage not found in specified layers; policy undecidable.');
        }

        // if the stageIndex is the highest layer, we are allowed to write
        if ($stageWriteDownPolicy->isHighestLayer($stageIndex) || $stageWriteDownPolicy->isLowestLayer($stageIndex)) {
            return true;
        }

        // get the next lower layer & the current layer and check if the target is contained in there
        $stagesToCheck = array_merge($stageWriteDownPolicy->getNextLowerLayer($stageIndex), $stageWriteDownPolicy->getLayer($stageIndex));

        return in_array($targetStage, $stagesToCheck);
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
        return null === $filter ||
            $filter->instanceMatches($this->copyShared->getSource()) ||
            $filter->instanceMatches($this->copyShared->getTarget());
    }
}
