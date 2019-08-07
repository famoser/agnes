<?php


namespace Agnes\Services;

use Agnes\Release\Release;
use Agnes\Services\Policy\ReleasePolicyVisitor;

class PolicyService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * PolicyService constructor.
     * @param ConfigurationService $configurationService
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @param Release $release
     * @throws \Exception
     */
    public function ensureCanRelease(Release $release)
    {
        $releasePolicyVisitor = new ReleasePolicyVisitor($release);
        $policies = $this->configurationService->getPolicies("release");

        foreach ($policies as $policy) {
            if (!$policy->accept($releasePolicyVisitor)) {
                throw new \Exception("policy denied execution: " . get_class($policy));
            }
        }
    }
}