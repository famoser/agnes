<?php

namespace Agnes\Services;

use Agnes\Models\Task\CopyShared;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Services\Policy\CopySharedPolicyVisitor;
use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\PolicyVisitor;
use Agnes\Services\Policy\ReleasePolicyVisitor;
use Agnes\Services\Policy\RollbackPolicyVisitor;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

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
     * @var StyleInterface
     */
    private $io;

    /**
     * PolicyService constructor.
     */
    public function __construct(ConfigurationService $configurationService, InstanceService $instanceService, StyleInterface $io)
    {
        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
        $this->io = $io;
    }

    /**
     * @throws Exception
     */
    public function canRelease(Release $release): bool
    {
        $releasePolicyVisitor = new ReleasePolicyVisitor($this->io, $release);

        return $this->noConflictingPolicies($releasePolicyVisitor, 'release');
    }

    /**
     * @throws Exception
     */
    public function canDeploy(Deploy $deploy): bool
    {
        $deployPolicyVisitor = new DeployPolicyVisitor($this->io, $this->instanceService, $deploy);

        return $this->noConflictingPolicies($deployPolicyVisitor, 'deploy');
    }

    /**
     * @throws Exception
     */
    public function canRollback(Rollback $rollback): bool
    {
        $roollbackPolicyVisitor = new RollbackPolicyVisitor($this->io, $rollback);

        return $this->noConflictingPolicies($roollbackPolicyVisitor, 'rollback');
    }

    /**
     * @throws Exception
     */
    public function canCopyShared(CopyShared $copyShared): bool
    {
        $copySharedPolicyVisitor = new CopySharedPolicyVisitor($this->io, $copyShared);

        return $this->noConflictingPolicies($copySharedPolicyVisitor, 'copy_shared');
    }

    /**
     * @throws Exception
     */
    private function noConflictingPolicies(PolicyVisitor $visitor, string $task): bool
    {
        $policies = $this->configurationService->getPolicies($task);

        $noConflictingPolicies = true;
        foreach ($policies as $policy) {
            if ($visitor->isApplicable($policy) && !$policy->accept($visitor)) {
                $noConflictingPolicies = false;
            }
        }

        return $noConflictingPolicies;
    }
}
