<?php


namespace Agnes\Services;

use Agnes\Models\Connections\LocalConnection;
use Agnes\Models\Connections\SSHConnection;
use Agnes\Models\Tasks\Task;

class TaskService
{
    /**
     * @param LocalConnection $connection
     * @param Task $task
     * @throws \Exception
     */
    public function executeLocal(LocalConnection $connection, Task $task)
    {
        $commands = $this->getCommands($task);

        // ensure working directory exists
        $workingFolderCommands = $this->ensureFolderExistsCommands($connection->getWorkingFolder());
        $commands = array_merge($workingFolderCommands, $commands);

        // change working directory
        chdir($connection->getWorkingFolder());

        // execute commands
        $this->executeCommands($commands);
    }

    /**
     * @param SSHConnection $connection
     * @param Task $task
     * @throws \Exception
     */
    public function executeSSH(SSHConnection $connection, Task $task)
    {
        $commands = $this->getCommands($task);

        // prefix all commands with SSH connection
        $workingFolder = $connection->getWorkingFolder();
        $sshPrefix = "ssh " . $connection->getDestination();
        foreach ($commands as &$command) {
            $command = $sshPrefix . " 'cd $workingFolder && $command'";
        }

        // ensure target dir exists
        $workingFolderCommands = $this->ensureFolderExistsCommands($workingFolder);
        foreach ($workingFolderCommands as &$workingFolderCommand) {
            $workingFolderCommand = $sshPrefix . " '" . $workingFolderCommand . "'";
        }

        $commands = array_merge($workingFolderCommands, $commands);

        // execute commands
        $this->executeCommands($commands);
    }

    /**
     * @param string[] $commands
     * @throws \Exception
     */
    private function executeCommands(array $commands): void
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
    private function getCommands(Task $task): array
    {
        // merge all commands to single list
        $commands = array_merge($task->getPreCommands(), $task->getCommands());

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
     * @param bool $clearFolder
     * @return string[]
     */
    private function ensureFolderExistsCommands(string $workingFolder): array
    {
        return ["rm -rf " . $workingFolder, "mkdir -m=0777 -p " . $workingFolder];
    }
}