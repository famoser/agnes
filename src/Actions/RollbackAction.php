<?php


namespace Agnes\Actions;


use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Exception;

class RollbackAction
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * RollbackService constructor.
     * @param ConfigurationService $configurationService
     * @param PolicyService $policyService
     * @param InstanceService $instanceService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, InstanceService $instanceService)
    {
        $this->configurationService = $configurationService;
        $this->policyService = $policyService;
        $this->instanceService = $instanceService;
    }

    /**
     * @param Rollback[] $rollbacks
     * @throws Exception
     */
    public function rollbackMultiple(array $rollbacks)
    {
        foreach ($rollbacks as $rollback) {
            $this->rollback($rollback);
        }
    }

    /**
     * @param Rollback $rollback
     * @throws Exception
     */
    private function rollback(Rollback $rollback)
    {
        if (!$this->policyService->canRollback($rollback)) {
            return;
        }

        $previousReleasePath = $rollback->getTarget()->getPath();
        $releaseFolder = $rollback->getInstance()->getCurrentInstallation()->getPath();

        // execute rollback task
        $deployScripts = $this->configurationService->getScripts("rollback");
        $rollback->getInstance()->getConnection()->executeScript($releaseFolder, $deployScripts, ["PREVIOUS_RELEASE_PATH" => $previousReleasePath]);

        $this->instanceService->switchRelease($rollback->getInstance(), $rollback->getTarget()->getRelease());
    }
}