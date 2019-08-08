<?php


namespace Agnes\Services\Policy;


use Agnes\Deploy\Deploy;
use Agnes\Models\Policies\StageWriteUpPolicy;
use Agnes\Models\Tasks\Filter;
use Agnes\Services\InstanceService;

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
     * @throws \Exception
     */
    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        $stageIndex = $this->getLayerIndex($stageWriteUpPolicy->getLayers(), $this->deployment->getTarget()->getStage());
        $checkIndex = $stageIndex - 1;

        // if the stageIndex is the lowest layer, we are allowed to write
        $availableLayers = array_keys($stageWriteUpPolicy->getLayers());
        if (min($availableLayers) > $checkIndex) {
            return true;
        }

        // get the next lower layer and check if this release was published there
        $stagesToCheck = $stageWriteUpPolicy->getLayers()[$checkIndex];
        $filter = new Filter([], [$this->deployment->getTarget()->getEnvironment()], $stagesToCheck);
        $instances = $this->installationService->getInstances($filter);

        foreach ($instances as $instance) {

        }


        return false;
    }

    private function getLayerIndex(array $layers, string $targetStage)
    {
        foreach ($layers as $index => $stage) {
            if ($stage === $targetStage) {
                return $index;
            }
        }

        throw new \Exception("Stage not found in specified layers; policy undecidable.");
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