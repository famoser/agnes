<?php


namespace Agnes\Services\Policy;


use Agnes\Deploy\Deployment;
use Agnes\Models\Policies\EnvironmentWriteUpPolicy;
use Agnes\Models\Tasks\Filter;
use Agnes\Services\InstanceService;

class DeployPolicyVisitor extends PolicyVisitor
{
    /**
     * @var InstanceService
     */
    private $installationService;

    /**
     * @var Deployment
     */
    private $deployment;

    /**
     * DeployPolicyVisitor constructor.
     * @param InstanceService $installationService
     * @param Deployment $deployment
     */
    public function __construct(InstanceService $installationService, Deployment $deployment)
    {
        $this->installationService = $installationService;
        $this->deployment = $deployment;
    }

    /**
     * @param EnvironmentWriteUpPolicy $environmentWriteUpPolicy
     * @return bool
     */
    public function visitEnvironmentWriteUp(EnvironmentWriteUpPolicy $environmentWriteUpPolicy)
    {
        $filter = new Filter([], [$this->deployment->getTarget()->getEnvironment()], []);
        $installations = $this->installationService->getInstances($filter);

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