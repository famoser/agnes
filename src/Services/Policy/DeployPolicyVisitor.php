<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Filter;
use Agnes\Models\Policy\StageWriteUpPolicy;
use Agnes\Models\Task\Deploy;
use Agnes\Services\InstanceService;
use Agnes\Services\Task\ExecutionVisitor\BuildResult;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

class DeployPolicyVisitor extends NoPolicyVisitor
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
     * @var BuildResult
     */
    private $buildResult;

    /**
     * DeployPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, InstanceService $installationService, Deploy $deploy, BuildResult $buildResult)
    {
        parent::__construct($io, $deploy);

        $this->installationService = $installationService;
        $this->buildResult = $buildResult;
    }

    /**
     * @throws Exception
     */
    protected function checkStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        if (!$this->filterMatches($stageWriteUpPolicy->getFilter())) {
            return true;
        }

        $targetStage = $this->deployment->getTarget()->getStage();
        $stageIndex = $stageWriteUpPolicy->getLayerIndex($targetStage);
        if (false === $stageIndex) {
            return $this->preventExecution($this->deployment, "Stage $targetStage not found in specified layers; policy undecidable.");
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
                if ($installation->getReleaseOrHash() === $this->buildResult->getReleaseOrHash()) {
                    return true;
                }
            }
        }

        return $this->preventExecution($this->deployment, "$targetStage not lowest stage, and release was never published in the next lower layer.");
    }

    protected function filterMatches(?Filter $filter): bool
    {
        return null === $filter || $filter->instanceMatches($this->deployment->getTarget());
    }
}
