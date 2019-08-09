<?php


namespace Agnes\Services\Policy;


use Agnes\Services\Deploy\Deploy;
use Agnes\Models\Policies\StageWriteUpPolicy;
use Agnes\Models\Filter;
use Agnes\Services\InstanceService;
use Exception;

class DeployPolicyVisitor extends PolicyVisitor
{
    /**
     * @var InstanceService
     */
    private $installationService;

    /**
     * @var Deploy
     */
    private $deployment;

    /**
     * DeployPolicyVisitor constructor.
     * @param InstanceService $installationService
     * @param Deploy $deployment
     */
    public function __construct(InstanceService $installationService, Deploy $deployment)
    {
        $this->installationService = $installationService;
        $this->deployment = $deployment;
    }

    /**
     * @param StageWriteUpPolicy $stageWriteUpPolicy
     * @return bool
     * @throws Exception
     */
    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        $targetStage = $this->deployment->getTarget()->getStage();
        $stageIndex = $stageWriteUpPolicy->getLayerIndex($targetStage);
        if ($stageIndex === false) {
            throw new Exception("Stage $targetStage not found in specified layers; policy undecidable.");
        }

        // if the stageIndex is the lowest layer, we are allowed to write
        if ($stageWriteUpPolicy->isLowestLayer($stageIndex)) {
            return true;
        }

        // get the next lower layer and check if this release was published there at any time
        $stagesToCheck = $stageWriteUpPolicy->getNextLowerLayer($stageIndex);
        $filter = new Filter([], [$this->deployment->getTarget()->getEnvironmentName()], $stagesToCheck);
        $instances = $this->installationService->getInstancesByFilter($filter);

        foreach ($instances as $instance) {
            foreach ($instance->getInstallations() as $installation) {
                if ($installation->hasOnlinePeriods() !== null && $installation->isSameRelease($this->deployment->getRelease()->getName())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Filter|null $filter
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return $filter === null || $filter->instanceMatches($this->deployment->getTarget());
    }
}