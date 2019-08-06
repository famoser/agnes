<?php


namespace Agnes\Services;

use Agnes\Services\Configuration\TaskConfig;

class TaskExecutionService
{
    /**
     * @param TaskConfig $taskConfig
     * @param array $envVariables
     * @throws \Exception
     */
    public function execute(TaskConfig $taskConfig, array $envVariables = [])
    {
        $this->setEnvironmentVariables($envVariables);
        $this->executeCommands($taskConfig);
    }

    /**
     * @param array $envVariables
     */
    private function setEnvironmentVariables(array $envVariables): void
    {
        foreach ($envVariables as $key => $entry) {
            exec("$key=$entry");
        }
    }

    /**
     * @param TaskConfig $taskConfig
     * @throws \Exception
     */
    private function executeCommands(TaskConfig $taskConfig): void
    {
        foreach ($taskConfig->getScript() as $command) {
            exec("cd " . $taskConfig->getWorkingFolder());
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception("command execution of " . $command . " failed with " . $returnVar . ". \n" . $output);
            }
        }
    }
}