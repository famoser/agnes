<?php


namespace Agnes\Models\Connections;

use Exception;

abstract class Connection
{
    /**
     * @param string $workingFolder
     * @param array $commands
     * @param array $envVariables
     */
    public function executeScript(string $workingFolder, array $commands, array $envVariables = [])
    {
        $commands = $this->prependEnvVariables($commands, $envVariables);

        $this->executeWithinWorkingFolder($workingFolder, $commands);
    }

    /**
     * @param string $workingFolder
     * @param string[] $commands
     * @return mixed
     */
    protected abstract function executeWithinWorkingFolder(string $workingFolder, array $commands);

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
     * @param string $command
     * @throws Exception
     */
    public function executeCommand(string $command): void
    {
        exec($command . " 2>&1", $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMessage = implode("\n", $output);
            throw new Exception("command execution of " . $command . " failed with " . $returnVar . " because $errorMessage.");
        }
    }

    /**
     * @param string[] $commands
     * @throws Exception
     */
    public function executeCommands(array $commands): void
    {
        // execute commands
        foreach ($commands as $command) {
            $this->executeCommand($command);
        }
    }

    /**
     * @param string[] $commands
     * @param string[] $envVariables
     * @return string[]
     */
    private function prependEnvVariables(array $commands, array $envVariables): array
    {
        // create env definition
        $envPrefix = "";
        foreach ($envVariables as $key => $value) {
            $envPrefix .= "$key=$value ";
        }

        // prefix env definition
        foreach ($commands as &$command) {
            $command = $envPrefix . $command;
        }

        return $commands;
    }
}