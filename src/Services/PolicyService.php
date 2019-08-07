<?php


namespace Agnes\Services;


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
     * @param string|null $commitish
     */
    public function ensureCanRelease(?string $commitish)
    {
        $this->configurationService->getPolicies("release");
    }
}