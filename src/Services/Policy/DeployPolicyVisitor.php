<?php


namespace Agnes\Services\Policy;


use Agnes\Deploy\Deployment;
use Agnes\Models\Policies\EnvironmentWriteUpPolicy;
use Agnes\Models\Tasks\Filter;
use Agnes\Services\Configuration\Installation;
use Agnes\Services\InstallationService;

class DeployPolicyVisitor implements PolicyVisitor
{
    /**
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var Deployment
     */
    private $deployment;

    /**
     * DeployPolicyVisitor constructor.
     * @param InstallationService $installationService
     * @param Deployment $deployment
     */
    public function __construct(InstallationService $installationService, Deployment $deployment)
    {
        $this->installationService = $installationService;
        $this->deployment = $deployment;
    }

    /**
     * @param EnvironmentWriteUpPolicy $environmentWriteUpPolicy
     * @return bool
     */
    public function visit(EnvironmentWriteUpPolicy $environmentWriteUpPolicy)
    {
        $filter = new Filter([], [$this->deployment->getTarget()->getEnvironment()], []);
        $installations = $this->installationService->getInstallations($filter);

    }
}