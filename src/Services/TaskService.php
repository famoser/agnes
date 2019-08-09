<?php


namespace Agnes\Services;

use Agnes\Models\Task;
use Exception;

class TaskService
{
    /**
     * @param string[] $commands
     * @throws Exception
     */
    public function executeCommands(array $commands): void
    {
        // execute commands
        foreach ($commands as $command) {
            var_dump("executing $command");
            exec($command . " 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMessage = implode("\n", $output);
                throw new Exception("command execution of " . $command . " failed with " . $returnVar . " because $errorMessage.");
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

        // create env definition
        $envPrefix = "";
        foreach ($task->getEnvVariables() as $key => $value) {
            $envPrefix .= "$key=$value ";
        }

        // prefix env definition
        foreach ($commands as &$command) {
            $command = $envPrefix . $command;
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