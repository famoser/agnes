<?php


namespace Agnes\Services;

use Agnes\Models\Connections\LocalConnection;
use Agnes\Models\Connections\SSHConnection;
use Agnes\Models\Tasks\Task;

class TaskService
{
    /**
     * @param string[] $commands
     * @throws \Exception
     */
    public function executeCommands(array $commands): void
    {
        // execute commands
        foreach ($commands as $command) {
            exec($command . " 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception("command execution of " . $command . " failed with " . $returnVar . ".");
            }
        }
    }

    /**
     * @param Task $task
     * @return string[]
     */
    public function getCommands(Task $task): array
    {
        // merge all commands to single list
        $commands = array_merge($task->getPreCommands(), $task->getCommands(), $task->getPostCommands());

        // replace env variables
        foreach ($task->getEnvVariables() as $key => $value) {
            foreach ($commands as &$command) {
                $command = str_replace("$$key", $value, $command);
            }
        }

        return $commands;
    }

    /**
     * @param string $workingFolder
     * @return string[]
     */
    public function ensureFolderExistsCommands(string $workingFolder): array
    {
        return ["rm -rf " . $workingFolder, "mkdir -m=0777 -p " . $workingFolder];
    }
}