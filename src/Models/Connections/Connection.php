<?php


namespace Agnes\Models\Connections;


use Agnes\Models\Task;
use Exception;

abstract class Connection
{
    /**
     * @param array $commands
     */
    public abstract function execute(...$commands);

    /**
     * @param Task $task
     */
    public abstract function executeTask(Task $task);

    /**
     * @param string $filePath
     * @return string
     */
    public abstract function readFile(string $filePath): string;

    /**
     * @param string $filePath
     * @param string $content
     */
    public abstract function writeFile(string $filePath, string $content);

    /**
     * @param string $dir
     * @return string[]
     */
    public abstract function getFolders(string $dir): array;

    /**
     * @param string $filePath
     * @return bool
     */
    public abstract function checkFileExists(string $filePath): bool;

    /**
     * @param string $folderPath
     * @return bool
     */
    public abstract function checkFolderExists(string $folderPath): bool;

    /**
     * @param Connection $connection
     * @return bool
     */
    public abstract function equals(Connection $connection): bool;


    /**
     * @param string[] $commands
     * @throws Exception
     */
    protected function executeCommands(array $commands): void
    {
        // execute commands
        foreach ($commands as $command) {
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
    protected function getCommands(Task $task): array
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
}