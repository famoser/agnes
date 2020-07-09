<?php

namespace Agnes\Models\Connection;

use Agnes\Models\Executor\Executor;
use Exception;

abstract class Connection
{
    /**
     * @var string[]
     */
    private $scriptOverrides = [];

    /**
     * @var Executor
     */
    private $executor;

    /**
     * Connection constructor.
     */
    public function __construct(Executor $executor)
    {
        $this->executor = $executor;
    }

    /**
     * @param string[] $scriptOverrides
     */
    public function setScriptOverrides(array $scriptOverrides): void
    {
        $this->scriptOverrides = $scriptOverrides;
    }

    /**
     * @throws Exception
     */
    public function executeScript(string $workingFolder, array $commands, array $envVariables = [])
    {
        $commands = $this->prependEnvVariables($commands, $envVariables);
        $commands = $this->applyScriptOverrides($commands);

        $this->executeWithinWorkingFolder($workingFolder, $commands);
    }

    /**
     * @param string[] $commands
     *
     * @throws Exception
     */
    abstract protected function executeWithinWorkingFolder(string $workingFolder, array $commands);

    abstract public function readFile(string $filePath): string;

    abstract public function writeFile(string $filePath, string $content);

    /**
     * @return string[]
     */
    abstract public function getFolders(string $dir): array;

    abstract public function checkFileExists(string $filePath): bool;

    abstract public function checkSymlinkExists(string $symlinkPath): bool;

    abstract public function checkFolderExists(string $folderPath): bool;

    abstract public function equals(Connection $connection): bool;

    /**
     * @throws Exception
     */
    protected function executeCommand(string $command): string
    {
        exec($command.' 2>&1', $output, $returnVar);

        $outputMessage = implode("\n", $output);
        if (0 !== $returnVar) {
            throw new Exception('command execution of '.$command.' failed with '.$returnVar." because $outputMessage.");
        }

        return $outputMessage;
    }

    /**
     * @param string[] $commands
     *
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
     *
     * @return string[]
     */
    private function prependEnvVariables(array $commands, array $envVariables): array
    {
        // create env definition
        $envPrefix = '';
        foreach ($envVariables as $key => $value) {
            $envPrefix .= "$key=$value ";
        }

        if (count($envVariables) > 0) {
            $envPrefix .= '&& ';
        }

        // prefix env definition
        foreach ($commands as &$command) {
            $command = $envPrefix.$command;
        }

        return $commands;
    }

    /**
     * @throws Exception
     */
    public function checkoutRepository(string $buildPath, string $repository, string $commitish)
    {
        $gitClone = $this->executor->gitClone($buildPath, $repository);
        $this->executeCommand($gitClone);

        $gitCheckout = $this->executor->gitCheckout($buildPath, $commitish);
        $this->executeCommand($gitCheckout);

        $gitShowHash = $this->executor->gitShowHash($buildPath);
        $hash = $this->executeCommand($gitShowHash);

        $removeRecursively = $this->executor->removeRecursive($buildPath.'/.git');
        $this->executeCommand($removeRecursively);

        return $hash;
    }

    /**
     * @throws Exception
     */
    public function createOrClearFolder(string $folder)
    {
        $command = $this->executor->removeRecursive($folder);
        $this->executeCommand($command);
        $this->createFolder($folder);
    }

    /**
     * @throws Exception
     */
    public function createFolder(string $folder)
    {
        $command = $this->executor->makeDirRecursive($folder);
        $this->executeCommand($command);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function compressTarGz(string $folder, string $fileName)
    {
        $command = $this->executor->compressTarGz($folder, $fileName);
        $this->executeCommand($command);

        return $folder.DIRECTORY_SEPARATOR.$fileName;
    }

    /**
     * @throws Exception
     */
    public function uncompressTarGz(string $archivePath, string $targetFolder)
    {
        $command = $this->executor->uncompressTarGz($archivePath, $targetFolder);
        $this->executeCommand($command);
    }

    /**
     * @throws Exception
     */
    public function removeFile(string $path)
    {
        $command = $this->executor->removeRecursive($path);
        $this->executeCommand($command);
    }

    /**
     * @throws Exception
     */
    public function createSymlink(string $source, string $target)
    {
        $relativeSharedFolder = $this->getRelativeSymlinkPath($source, $target);
        $command = $this->executor->createSymbolicLink($source, $relativeSharedFolder);
        $this->executeCommand($command);
    }

    /**
     * @throws Exception
     */
    public function readSymlink(string $symlink): string
    {
        $command = $this->executor->readSymbolicLink($symlink);

        return $this->executeCommand($command);
    }

    /**
     * @return string
     */
    private function getRelativeSymlinkPath(string $source, string $target)
    {
        $sourceArray = explode(DIRECTORY_SEPARATOR, $source);
        $targetArray = explode(DIRECTORY_SEPARATOR, $target);

        // get count of entries equal for both paths
        $equalEntries = 0;
        while (($sourceArray[$equalEntries] === $targetArray[$equalEntries])) {
            ++$equalEntries;
        }

        // if some equal found, then cut how much path we need from the target in the resulting relative path
        if ($equalEntries > 0) {
            $targetArray = array_slice($targetArray, $equalEntries);
        }

        // find out how many levels we need to go back until we can start the relative target path
        $levelsBack = count($sourceArray) - $equalEntries - 1;

        return str_repeat('..'.DIRECTORY_SEPARATOR, $levelsBack).implode(DIRECTORY_SEPARATOR, $targetArray);
    }

    /**
     * @throws Exception
     */
    public function moveFolder(string $source, string $target)
    {
        $command = $this->executor->moveAndReplace($source, $target);
        $this->executeCommand($command);
    }

    /**
     * @throws Exception
     */
    public function copyFolderContent(string $source, string $target)
    {
        $sourceContent = $source.DIRECTORY_SEPARATOR.'.';
        $command = $this->executor->copyRecursive($sourceContent, $target);
        $this->executeCommand($command);
    }

    /**
     * @throws Exception
     */
    public function removeFolder(string $folder)
    {
        $command = $this->executor->removeRecursive($folder);
        $this->executeCommand($command);
    }

    /**
     * @throws Exception
     */
    public function replaceSymlink(string $source, string $target)
    {
        $command = $this->executor->replaceSymlink($source, $target);
        $this->executeCommand($command);
    }

    /**
     * @param string[] $commands
     *
     * @return array|string[]
     */
    private function applyScriptOverrides(array $commands)
    {
        $overrideMatch = '/{{[^{}]*}}/';

        foreach ($commands as &$command) {
            preg_match_all($overrideMatch, $command, $matches);

            $replaces = [];
            foreach ($matches[0] as $match) {
                $content = substr($match, 2, -2); // cut off {{ and }}
                $newValue = isset($this->scriptOverrides[$content]) ? $this->scriptOverrides[$content] : $content;

                $replaces[$match] = $newValue;
            }

            $command = str_replace(array_keys($replaces), array_values($replaces), $command);
        }

        return $commands;
    }

    /**
     * @param string[] $fullPaths
     *
     * @return string[]
     */
    protected function keepFolderOnly(array $fullPaths): array
    {
        $folder = [];
        foreach ($fullPaths as $fullPath) {
            $lastSlash = strrpos($fullPath, '/');
            if (false === $lastSlash) {
                $folder[] = $fullPath;
            } else {
                $folder[] = substr($fullPath, $lastSlash + 1);
            }
        }

        return $folder;
    }
}
