<?php


namespace Agnes\Services;


use Agnes\Models\Tasks\Task;
use Agnes\Services\Rollback\Rollback;
use Exception;

class RollbackService
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
     * @var TaskService
     */
    private $taskService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * RollbackService constructor.
     * @param ConfigurationService $configurationService
     * @param TaskService $taskService
     * @param InstanceService $instanceService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, TaskService $taskService, InstanceService $instanceService)
    {
        $this->configurationService = $configurationService;
        $this->policyService = $policyService;
        $this->taskService = $taskService;
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
        $task = new Task($releaseFolder, $deployScripts, ["PREVIOUS_RELEASE_PATH" => $previousReleasePath]);
        $rollback->getInstance()->getConnection()->executeTask($task, $this->taskService);

        $this->instanceService->switchRelease($rollback->getInstance(), $rollback->getTarget()->getRelease());
    }
}