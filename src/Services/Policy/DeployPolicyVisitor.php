<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Policy;

use Agnes\Models\Filter;
use Agnes\Models\Policy\StageWriteUpPolicy;
use Agnes\Models\Task\Deploy;
use Agnes\Services\InstanceService;
use Agnes\Services\Task\ExecutionVisitor\BuildResult;
use Symfony\Component\Console\Style\StyleInterface;

class DeployPolicyVisitor extends NeedsBuildResultPolicyVisitor
{
    /**
     * @var InstanceService
     */
    private $installationService;

    /**
     * @var Deploy
     */
    private $deploy;

    /**
     * @var BuildResult|null
     */
    private $buildResult;

    /**
     * DeployPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, InstanceService $installationService, ?BuildResult $buildResult, Deploy $deploy)
    {
        parent::__construct($io, $buildResult, $deploy);

        $this->installationService = $installationService;
        $this->deploy = $deploy;
        $this->buildResult = $buildResult;
    }

    /**
     * @throws \Exception
     */
    protected function checkStageWriteUp(StageWriteUpPolicy $policy): bool
    {
        if (!$this->filterMatches($policy->getFilter())) {
            return true;
        }

        $targetStage = $this->deploy->getTarget()->getStage();
        $stageIndex = $policy->getLayerIndex($targetStage);

        // if stage not part of policy
        if (false === $stageIndex) {
            return true;
        }

        // if the stageIndex is the lowest layer, we are allowed to write
        if ($policy->isLowestLayer($stageIndex)) {
            return true;
        }

        // get all instances of the next lower layer
        $stagesToCheck = $policy->getNextLowerLayer($stageIndex);
        $filter = new Filter(null, [$this->deploy->getTarget()->getEnvironmentName()], $stagesToCheck);
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

        return $this->policyPreventsExecution($policy, "$targetStage not lowest stage, and release was never published in the next lower layer.");
    }

    protected function filterMatches(?Filter $filter): bool
    {
        return null === $filter || $filter->instanceMatches($this->deploy->getTarget());
    }
}
