<?php

namespace Agnes\Services\Policy;

use Agnes\Actions\Deploy;
use Agnes\Models\Filter;
use Agnes\Models\Policies\StageWriteUpPolicy;
use Agnes\Services\InstanceService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

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
    public function __construct(OutputInterface $output, InstanceService $installationService, Deploy $deployment)
    {
        parent::__construct($output);

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
            $this->preventExecution($this->deployment, "Stage $targetStage not found in specified layers; policy undecidable.");

            return false;
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
                if ($installation->setupMatches($this->deployment->getSetup()->getIdentification())) {
                    return true;
                }
            }
        }

        $this->preventExecution($this->deployment, "$targetStage not lowest stage, and release was never published in the next lower layer.");

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
