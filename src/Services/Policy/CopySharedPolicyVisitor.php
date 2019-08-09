<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Policies\SameReleasePolicy;
use Agnes\Models\Policies\StageWriteDownPolicy;
use Agnes\Models\Tasks\Filter;
use Agnes\Services\CopyShared\CopyShared;
use Exception;

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
     * @param SameReleasePolicy $sameReleasePolicy
     * @return bool
     */
    public function visitSameRelease(SameReleasePolicy $sameReleasePolicy): bool
    {
        $sourceRelease = $this->copyShared->getSource()->getCurrentInstallation()->getRelease()->getName();
        $targetRelease = $this->copyShared->getTarget()->getCurrentInstallation()->getRelease()->getName();

        return $sourceRelease === $targetRelease;
    }

    /**
     * @param StageWriteDownPolicy $stageWriteDownPolicy
     * @return bool
     * @throws \Exception
     */
    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        $targetStage = $this->copyShared->getTarget()->getStage();
        $sourceStage = $this->copyShared->getSource()->getStage();

        $stageIndex = $stageWriteDownPolicy->getLayerIndex($sourceStage);
        if ($stageIndex === false) {
            throw new Exception("Stage not found in specified layers; policy undecidable.");
        }

        // if the stageIndex is the highest layer, we are allowed to write
        if ($stageWriteDownPolicy->isHighestLayer($stageIndex) || $stageWriteDownPolicy->isLowestLayer($targetStage)) {
            return true;
        }

        // get the next lower layer and check if the target is contained in there
        $stagesToCheck = $stageWriteDownPolicy->getNextLowerLayer($stageIndex);
        return in_array($targetStage, $stagesToCheck);
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