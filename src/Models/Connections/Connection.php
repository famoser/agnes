<?php


namespace Agnes\Models\Connections;

use Exception;

abstract class Connection
{
    /**
     * @var string[]
     */
    private $scriptOverrides = [];

    /**
     * @param string[] $scriptOverrides
     */
    public function setScriptOverrides(array $scriptOverrides): void
    {
        $this->scriptOverrides = $scriptOverrides;
    }

    /**
     * @param string $workingFolder
     * @param array $commands
     * @param array $envVariables
     * @throws Exception
     */
    public function executeScript(string $workingFolder, array $commands, array $envVariables = [])
    {
        $commands = $this->prependEnvVariables($commands, $envVariables);
        $commands = $this->applyScriptOverrides($commands);

        $this->executeWithinWorkingFolder($workingFolder, $commands);
    }

    /**
     * @param string $workingFolder
     * @param string[] $commands
     * @throws Exception
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
     * @return string
     * @throws Exception
     */
    protected function executeCommand(string $command): string
    {
        exec($command . " 2>&1", $output, $returnVar);

        $outputMessage = implode("\n", $output);
        if ($returnVar !== 0) {
            throw new Exception("command execution of " . $command . " failed with " . $returnVar . " because $outputMessage.");
        }

        return $outputMessage;
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

    /**
     * @param string $buildPath
     * @param string $repository
     * @param string $commitish
     * @throws Exception
     */
    public function checkoutRepository(string $buildPath, string $repository, string $commitish)
    {
        $this->executeScript($buildPath, [
            "git clone git@github.com:" . $repository . " .",
            "git checkout " . $commitish,
            "rm -rf .git"
        ]);
    }

    /**
     * @param string $folder
     * @throws Exception
     */
    public function createOrClearFolder(string $folder)
    {
        $this->executeCommand("rm -rf " . $folder);
        $this->createFolder($folder);
    }

    /**
     * @param string $folder
     * @throws Exception
     */
    public function createFolder(string $folder)
    {
        $this->executeCommand("mkdir -m 0777 -p $folder");
    }

    /**
     * @param string $folder
     * @param string $fileName
     * @return string
     * @throws Exception
     */
    public function compressTarGz(string $folder, string $fileName)
    {
        $this->executeScript($folder, [
            "touch $fileName",
            "tar -czvf $fileName --exclude=$fileName ."
        ]);

        return $folder . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @param string $archivePath
     * @param string $targetFolder
     * @throws Exception
     */
    public function uncompressTarGz(string $archivePath, string $targetFolder)
    {
        $this->executeCommand("tar -xzf $archivePath -C $targetFolder");
    }

    /**
     * @param string $path
     * @throws Exception
     */
    public function removeFile(string $path)
    {
        $this->executeCommand("rm $path");
    }

    /**
     * @param string $source
     * @param string $target
     * @throws Exception
     */
    public function createSymlink(string $source, string $target)
    {
        $relativeSharedFolder = $this->getRelativeSymlinkPath($source, $target);
        $this->executeCommand("ln -s $relativeSharedFolder $source");
    }

    /**
     * @param string $source
     * @param string $target
     * @return string
     */
    private function getRelativeSymlinkPath(string $source, string $target)
    {
        $sourceArray = explode(DIRECTORY_SEPARATOR, $source);
        $targetArray = explode(DIRECTORY_SEPARATOR, $target);

        // get count of entries equal for both paths
        $equalEntries = 0;
        while (($sourceArray[$equalEntries] === $targetArray[$equalEntries])) {
            $equalEntries++;
        }

        // if some equal found, then cut how much path we need from the target in the resulting relative path
        if ($equalEntries > 0) {
            $targetArray = array_slice($targetArray, $equalEntries);
        }

        // find out how many levels we need to go back until we can start the relative target path
        $levelsBack = count($sourceArray) - $equalEntries - 1;

        return str_repeat(".." . DIRECTORY_SEPARATOR, $levelsBack) . implode(DIRECTORY_SEPARATOR, $targetArray);
    }

    /**
     * @param string $source
     * @param string $target
     * @throws Exception
     */
    public function moveFolder(string $source, string $target)
    {
        $this->executeCommand("mv $source $target");
    }

    /**
     * @param string $source
     * @param string $target
     * @throws Exception
     */
    public function copyFolderContent(string $source, string $target)
    {
        $sourceContent = $source . DIRECTORY_SEPARATOR . ".";
        $this->executeCommand("cp -r $sourceContent $target");
    }

    /**
     * @param string $folder
     * @throws Exception
     */
    public function removeFolder(string $folder)
    {
        $this->executeCommand("rm -rf $folder");
    }

    /**
     * @param string $source
     * @param string $target
     * @throws Exception
     */
    public function moveFile(string $source, string $target)
    {
        $this->executeCommand("mv $source $target");
    }

    /**
     * @param string[] $commands
     * @return array|string[]
     */
    private function applyScriptOverrides(array $commands)
    {
        $overrideMatch = "/{{[^{}]*}}/";

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
}