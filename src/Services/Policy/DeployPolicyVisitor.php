<?php


namespace Agnes\Services\Policy;


use Agnes\Actions\Deploy;
use Agnes\Models\Filter;
use Agnes\Models\Policies\StageWriteUpPolicy;
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

        // get all instances of the next lower layer
        $stagesToCheck = $stageWriteUpPolicy->getNextLowerLayer($stageIndex);
        $filter = new Filter(null, [$this->deployment->getTarget()->getEnvironmentName()], $stagesToCheck);
        $instances = $this->installationService->getInstancesByFilter($filter);

        // if no instances exist of the specified stages fulfil the policy trivially
        if (count($instances) === 0) {
            return true;
        }

        // check if the release was published there at any given time
        foreach ($instances as $instance) {
            foreach ($instance->getInstallations() as $installation) {
                if ($installation->hasOnlinePeriods() !== null && $installation->isSameReleaseName($this->deployment->getBuild()->getName())) {
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
