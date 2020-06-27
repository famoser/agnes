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
     */
    public function __construct(InstanceService $installationService, Deploy $deployment)
    {
        $this->installationService = $installationService;
        $this->deployment = $deployment;
    }

    /**
     * @throws Exception
     */
    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        $targetStage = $this->deployment->getTarget()->getStage();
        $stageIndex = $stageWriteUpPolicy->getLayerIndex($targetStage);
        if (false === $stageIndex) {
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
        if (0 === count($instances)) {
            return true;
        }

        // check if the release was published there at any given time
        foreach ($instances as $instance) {
            foreach ($instance->getInstallations() as $installation) {
                if (null !== $installation->hasOnlinePeriods() && $installation->isSameReleaseName($this->deployment->getBuild()->getName())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return null === $filter || $filter->instanceMatches($this->deployment->getTarget());
    }
}
