<?php


namespace Agnes\Services;

use Agnes\Services\Deploy\Deploy;
use Agnes\Models\Policies\Policy;
use Agnes\Services\Release\Release;
use Agnes\Services\CopyShared\CopyShared;
use Agnes\Services\Policy\CopySharedPolicyVisitor;
use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\PolicyVisitor;
use Agnes\Services\Policy\ReleasePolicyVisitor;
use Agnes\Services\Policy\RollbackPolicyVisitor;
use Agnes\Services\Rollback\Rollback;
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
        if (($policy = $this->canExecute($releasePolicyVisitor, "release"))) {
            throw new Exception("policy denied execution: " . get_class($policy));
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

        return $this->canExecute($deployPolicyVisitor, "deploy") === null;
    }

    /**
     * @param Rollback $rollback
     * @return bool
     * @throws Exception
     */
    public function canRollback(Rollback $rollback): bool
    {
        $roollbackPolicyVisitor = new RollbackPolicyVisitor($rollback);

        return $this->canExecute($roollbackPolicyVisitor, "rollback") === null;
    }

    /**
     * @param CopyShared $copyShared
     * @return bool
     * @throws Exception
     */
    public function canCopyShared(CopyShared $copyShared): bool
    {
        $copySharedPolicyVisitor = new CopySharedPolicyVisitor($copyShared);

        return $this->canExecute($copySharedPolicyVisitor, "copy_shared") === null;
    }

    /**
     * @param PolicyVisitor $visitor
     * @param string $task
     * @return Policy|null
     * @throws Exception
     */
    private function canExecute(PolicyVisitor $visitor, string $task): ?Policy
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