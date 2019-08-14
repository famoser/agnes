<?php


namespace Agnes\Actions;


use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Exception;

class RollbackAction extends AbstractAction
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
        parent::__construct($policyService);

        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute()
     *
     * @param Rollback $payload
     * @return bool
     */
    protected function canProcessPayload($payload): bool
    {
        return $payload instanceof Rollback;
    }

    /**
     * @param Rollback $rollback
     * @throws Exception
     */
    protected function doExecute($rollback)
    {
        $previousReleasePath = $rollback->getTarget()->getPath();
        $releaseFolder = $rollback->getInstance()->getCurrentInstallation()->getPath();

        // execute rollback task
        $deployScripts = $this->configurationService->getScripts("rollback");
        $rollback->getInstance()->getConnection()->executeScript($releaseFolder, $deployScripts, ["PREVIOUS_RELEASE_PATH" => $previousReleasePath]);

        $this->instanceService->switchRelease($rollback->getInstance(), $rollback->getTarget()->getRelease());
    }
}