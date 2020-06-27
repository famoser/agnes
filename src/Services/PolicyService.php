<?php

namespace Agnes\Services;

use Agnes\Actions\CopyShared;
use Agnes\Actions\Deploy;
use Agnes\Actions\Release;
use Agnes\Actions\Rollback;
use Agnes\Services\Policy\CopySharedPolicyVisitor;
use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\PolicyVisitor;
use Agnes\Services\Policy\ReleasePolicyVisitor;
use Agnes\Services\Policy\RollbackPolicyVisitor;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

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
    public function canRelease(Release $release, OutputInterface $output): bool
    {
        $releasePolicyVisitor = new ReleasePolicyVisitor($output, $release);

        return $this->noConflictingPolicies($releasePolicyVisitor, 'release');
    }

    /**
     * @throws Exception
     */
    public function canDeploy(Deploy $deploy, OutputInterface $output): bool
    {
        $deployPolicyVisitor = new DeployPolicyVisitor($output, $this->instanceService, $deploy);

        return $this->noConflictingPolicies($deployPolicyVisitor, 'deploy');
    }

    /**
     * @throws Exception
     */
    public function canRollback(Rollback $rollback, OutputInterface $output): bool
    {
        $roollbackPolicyVisitor = new RollbackPolicyVisitor($output, $rollback);

        return $this->noConflictingPolicies($roollbackPolicyVisitor, 'rollback');
    }

    /**
     * @throws Exception
     */
    public function canCopyShared(CopyShared $copyShared, OutputInterface $output): bool
    {
        $copySharedPolicyVisitor = new CopySharedPolicyVisitor($output, $copyShared);

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
