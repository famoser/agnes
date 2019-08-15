<?php


namespace Agnes\Actions;


use Agnes\Models\Installation;
use Agnes\Models\Instance;
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
     * @param Instance $instance
     * @param string|null $rollbackTo
     * @param string|null $rollbackFrom
     * @return Installation|null
     */
    public function getRollbackTarget(Instance $instance, ?string $rollbackTo, ?string $rollbackFrom): ?Installation
    {
        // ensure instance active
        if ($instance->getCurrentInstallation() === null) {
            return null;
        }

        // ensure rollbackFrom is what is currently active
        if ($rollbackFrom !== null && !$instance->isCurrentRelease($rollbackFrom)) {
            return null;
        }

        // if no rollback target specified, return the previous installation
        if ($rollbackTo === null) {
            return $instance->getPreviousInstallation();
        }

        // ensure target is not same than current release
        if ($instance->isCurrentRelease($rollbackTo)) {
            return null;
        }

        // find matching installation & ensure it is indeed a previous release
        $targetInstallation = $instance->getInstallation($rollbackTo);
        if ($targetInstallation !== null && $targetInstallation->getNumber() < $instance->getCurrentInstallation()->getNumber()) {
            return $targetInstallation;
        }

        return null;
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