<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Models\Connection;

use Agnes\Models\Executor\Executor;
use Symfony\Component\Console\Style\OutputStyle;

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
     * @var OutputStyle
     */
    private $io;

    /**
     * Connection constructor.
     */
    public function __construct(OutputStyle $io, Executor $executor)
    {
        $this->executor = $executor;
        $this->io = $io;
    }

    /**
     * @param string[] $scriptOverrides
     */
    public function setScriptOverrides(array $scriptOverrides): void
    {
        $this->scriptOverrides = $scriptOverrides;
    }

    /**
     * @throws \Exception
     */
    public function executeScript(string $workingFolder, array $commands, array $envVariables = []): void
    {
        $commands = $this->prependEnvVariables($commands, $envVariables);
        $commands = $this->applyScriptOverrides($commands);

        $this->executeWithinWorkingFolder($workingFolder, $commands);
    }

    /**
     * @param string[] $commands
     *
     * @throws \Exception
     */
    abstract protected function executeWithinWorkingFolder(string $workingFolder, array $commands): void;

    abstract public function readFile(string $filePath): string;

    abstract public function writeFile(string $filePath, string $content): void;

    /**
     * @return string[]
     */
    abstract public function getFolders(string $dir): array;

    abstract public function checkFileExists(string $filePath): bool;

    abstract public function checkSymlinkExists(string $symlinkPath): bool;

    abstract public function checkFolderExists(string $folderPath): bool;

    abstract public function equals(Connection $connection): bool;

    /**
     * @throws \Exception
     */
    protected function executeCommand(string $command): string
    {
        if ($this->io->isVerbose()) {
            $this->io->text('executing '.$command);
        }

        exec($command.' 2>&1', $output, $returnVar);

        $outputMessage = implode("\n", $output);
        if (0 !== $returnVar) {
            throw new \Exception('command execution of '.$command.' failed with '.$returnVar." because $outputMessage.");
        }

        return $outputMessage;
    }

    /**
     * @param string[] $commands
     *
     * @throws \Exception
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
     * @throws \Exception
     */
    public function getRepositoryStateAtCommitish(string $path, string $repository, string $commitish): string
    {
        $this->checkoutRepository($path, $repository);

        $gitCheckout = $this->executor->gitCheckout($path, $commitish);
        $this->executeCommand($gitCheckout);

        $gitShowHash = $this->executor->gitShowHash($path);
        $hash = $this->executeCommand($gitShowHash);

        $removeRecursively = $this->executor->rmRecursive($path.'/.git');
        $this->executeCommand($removeRecursively);

        return $hash;
    }

    /**
     * @throws \Exception
     */
    public function checkoutRepository(string $path, string $repository): void
    {
        $gitClone = $this->executor->gitClone($path, $repository);
        $this->executeCommand($gitClone);
    }

    /**
     * @throws \Exception
     */
    public function gitPull(string $path): void
    {
        $gitPull = $this->executor->gitPull($path);
        $this->executeCommand($gitPull);
    }

    /**
     * @throws \Exception
     */
    public function createOrClearFolder(string $folder): void
    {
        $command = $this->executor->rmRecursive($folder);
        $this->executeCommand($command);
        $this->createFolder($folder);
    }

    /**
     * @throws \Exception
     */
    public function createFolder(string $folder): void
    {
        $command = $this->executor->mkdirRecursive($folder);
        $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function compressTarGz(string $folder, string $fileName): string
    {
        $targetFilePath = $folder.'/'.$fileName;
        $command = $this->executor->rmIfExists($targetFilePath);
        $this->executeCommand($command);

        $command = $this->executor->touch($targetFilePath);
        $this->executeCommand($command);

        $command = $this->executor->tarCompressInSameFolder($folder, $fileName);
        $this->executeCommand($command);

        return $folder.DIRECTORY_SEPARATOR.$fileName;
    }

    /**
     * @throws \Exception
     */
    public function uncompressTarGz(string $archivePath, string $targetFolder): void
    {
        $command = $this->executor->tarUncompress($archivePath, $targetFolder);
        $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function removeFile(string $path): void
    {
        $command = $this->executor->rmRecursive($path);
        $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function createSymlink(string $source, string $target): void
    {
        $relativeSharedFolder = $this->getRelativeSymlinkPath($source, $target);
        $command = $this->executor->lnCreateSymbolicLink($source, $relativeSharedFolder);
        $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function readSymlink(string $symlink): string
    {
        $command = $this->executor->readlinkCanonicalize($symlink);

        return $this->executeCommand($command);
    }

    private function getRelativeSymlinkPath(string $source, string $target): string
    {
        $sourceArray = explode(DIRECTORY_SEPARATOR, $source);
        $targetArray = explode(DIRECTORY_SEPARATOR, $target);

        // get count of entries equal for both paths
        $equalEntries = 0;
        while ($sourceArray[$equalEntries] === $targetArray[$equalEntries]) {
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
     * @throws \Exception
     */
    public function absolutePath(string $relativePath): string
    {
        $command = $this->executor->readlinkCanonicalize($relativePath);

        return $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function moveFolder(string $source, string $target): void
    {
        $command = $this->executor->rmMoveAndReplace($source, $target);
        $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function copyFolderContent(string $source, string $target): void
    {
        $sourceContent = $source.DIRECTORY_SEPARATOR.'.';
        $command = $this->executor->cpRecursive($sourceContent, $target);
        $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function removeFolder(string $folder): void
    {
        $command = $this->executor->rmRecursive($folder);
        $this->executeCommand($command);
    }

    /**
     * @throws \Exception
     */
    public function replaceSymlink(string $source, string $target): void
    {
        $command = $this->executor->mvSymlinkAtomicReplace($source, $target);
        $this->executeCommand($command);
    }

    /**
     * @param string[] $commands
     *
     * @return string[]
     */
    private function applyScriptOverrides(array $commands): array
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
