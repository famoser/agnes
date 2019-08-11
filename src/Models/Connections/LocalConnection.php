<?php


namespace Agnes\Models\Connections;


use Exception;
use function chdir;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_file;
use const GLOB_ONLYDIR;

class LocalConnection extends Connection
{
    /**
     * @param string $workingFolder
     * @param array $commands
     * @throws Exception
     */
    protected function executeWithinWorkingFolder(string $workingFolder, array $commands)
    {
        // change working directory
        $originWorkingFolder = getcwd();
        chdir($workingFolder);

        // execute commands
        $this->executeCommands($commands);

        // recover working directory
        chdir($originWorkingFolder);
    }

    /**
     * @param string $filePath
     * @return string
     */
    public function readFile(string $filePath): string
    {
        return file_get_contents($filePath);
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public function writeFile(string $filePath, string $content)
    {
        file_put_contents($filePath, $content);
    }

    /**
     * @param string $dir
     * @return string[]
     */
    public function getFolders(string $dir): array
    {
        return glob("$dir/*", GLOB_ONLYDIR);
    }

    /**
     * @param string $filePath
     * @return bool
     */
    public function checkFileExists(string $filePath): bool
    {
        return is_file($filePath);
    }

    /**
     * @param string $folderPath
     * @return bool
     */
    public function checkFolderExists(string $folderPath): bool
    {
        return is_dir($folderPath);
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function equals(Connection $connection): bool
    {
        return $connection instanceof LocalConnection;
    }

    /**
     * @param string $path
     */
    public function removeFile(string $path)
    {
        unlink($path);
    }
}