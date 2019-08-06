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

        // create build folder
        if (!is_dir($taskConfig->getWorkingFolder())) {
            mkdir($taskConfig->getWorkingFolder(), 0777, true);
        }

        // change working directory
        chdir($taskConfig->getWorkingFolder());

        // execute commands
        $this->executeCommands($taskConfig->getPrependCommands());
        $this->executeCommands($taskConfig->getCommands());
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
     * @param array $commands
     * @throws \Exception
     */
    private function executeCommands(array $commands): void
    {
        // execute commands
        foreach ($commands as $command) {
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception("command execution of " . $command . " failed with " . $returnVar . ".");
            }
        }
    }
}