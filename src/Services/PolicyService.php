<?php


namespace Agnes\Services;

use Agnes\Deploy\Deploy;
use Agnes\Release\Release;
use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\ReleasePolicyVisitor;
use Exception;

class PolicyService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * PolicyService constructor.
     * @param ConfigurationService $configurationService
     * @param InstanceService $instanceService
     */
    public function __construct(ConfigurationService $configurationService, InstanceService $instanceService)
    {
        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
    }

    /**
     * @param Release $release
     * @throws Exception
     */
    public function ensureCanRelease(Release $release)
    {
        $releasePolicyVisitor = new ReleasePolicyVisitor($release);
        $policies = $this->configurationService->getPolicies("release");

        foreach ($policies as $policy) {
            if (!$policy->accept($releasePolicyVisitor)) {
                throw new Exception("policy denied execution: " . get_class($policy));
            }
        }
    }

    /**
     * @param Deploy $deploy
     * @return bool
     * @throws Exception
     */
    public function canDeploy(Deploy $deploy): bool
    {
        $deployPolicyVisitor = new DeployPolicyVisitor($this->instanceService, $deploy);
        $policies = $this->configurationService->getPolicies("deploy");

        foreach ($policies as $policy) {
            if (!$policy->accept($deployPolicyVisitor)) {
                return false;
            }
        }

        return true;
    }
}