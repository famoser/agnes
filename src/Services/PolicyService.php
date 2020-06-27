<?php

namespace Agnes\Services;

use Agnes\Actions\CopyShared;
use Agnes\Actions\Deploy;
use Agnes\Actions\Release;
use Agnes\Actions\Rollback;
use Agnes\Models\Policies\Policy;
use Agnes\Services\Policy\CopySharedPolicyVisitor;
use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\PolicyVisitor;
use Agnes\Services\Policy\ReleasePolicyVisitor;
use Agnes\Services\Policy\RollbackPolicyVisitor;
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
     */
    public function __construct(ConfigurationService $configurationService, InstanceService $instanceService)
    {
        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
    }

    /**
     * @throws Exception
     */
    public function canRelease(Release $release): bool
    {
        $releasePolicyVisitor = new ReleasePolicyVisitor($release);

        return null === $this->getConflictingPolicy($releasePolicyVisitor, 'release');
    }

    /**
     * @throws Exception
     */
    public function canDeploy(Deploy $deploy): bool
    {
        $deployPolicyVisitor = new DeployPolicyVisitor($this->instanceService, $deploy);

        return null === $this->getConflictingPolicy($deployPolicyVisitor, 'deploy');
    }

    /**
     * @throws Exception
     */
    public function canRollback(Rollback $rollback): bool
    {
        $roollbackPolicyVisitor = new RollbackPolicyVisitor($rollback);

        return null === $this->getConflictingPolicy($roollbackPolicyVisitor, 'rollback');
    }

    /**
     * @throws Exception
     */
    public function canCopyShared(CopyShared $copyShared): bool
    {
        $copySharedPolicyVisitor = new CopySharedPolicyVisitor($copyShared);

        return null === $this->getConflictingPolicy($copySharedPolicyVisitor, 'copy_shared');
    }

    /**
     * @throws Exception
     */
    private function getConflictingPolicy(PolicyVisitor $visitor, string $task): ?Policy
    {
        $policies = $this->configurationService->getPolicies($task);

        foreach ($policies as $policy) {
            if ($visitor->isApplicable($policy) && !$policy->accept($visitor)) {
                return $policy;
            }
        }

        return null;
    }
}
