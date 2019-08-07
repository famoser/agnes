<?php


namespace Agnes\Services;

use Agnes\Models\Tasks\LocalTask;
use Agnes\Models\Tasks\SSHTask;
use Agnes\Models\Tasks\Task;

class TaskExecutionService
{
    /**
     * @param LocalTask $task
     * @throws \Exception
     */
    public function executeLocal(LocalTask $task)
    {
        $commands = $this->getCommands($task);

        // ensure working directory exists
        $workingFolderCommands = $this->ensureFolderExistsCommands($task->getWorkingFolder(), $task->getClearWorkingFolder());
        $commands = array_merge($workingFolderCommands, $commands);

        // change working directory
        chdir($task->getWorkingFolder());

        // execute commands
        $this->executeCommands($commands);
    }

    /**
     * @param SSHTask $task
     * @throws \Exception
     */
    public function executeSSH(SSHTask $task)
    {
        $commands = $this->getCommands($task);

        // prefix all commands with SSH connection
        $workingFolder = $task->getWorkingFolder();
        $sshPrefix = "ssh " . $task->getDestination();
        foreach ($commands as &$command) {
            $command = $sshPrefix . " 'cd $workingFolder && $command'";
        }

        // ensure target dir exists
        $workingFolderCommands = $this->ensureFolderExistsCommands($workingFolder, $task->getClearWorkingFolder());
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
            exec($command, $output, $returnVar);

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
        $commands = array_merge($task->getPrependCommands(), $task->getCommands());

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
    private function ensureFolderExistsCommands(string $workingFolder, bool $clearFolder = false): array
    {
        $commands = [];

        if ($clearFolder) {
            $commands[] = "rm -rf " . $workingFolder;
        }

        $commands[] = "mkdir -m=0777 -p " . $workingFolder;

        return $commands;
    }
}