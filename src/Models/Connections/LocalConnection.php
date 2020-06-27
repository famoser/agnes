<?php

namespace Agnes\Models\Connections;

use function chdir;
use Exception;
use function file_get_contents;
use function file_put_contents;
use function glob;
use const GLOB_ONLYDIR;
use function is_file;

class LocalConnection extends Connection
{
    /**
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

    public function readFile(string $filePath): string
    {
        return file_get_contents($filePath);
    }

    public function writeFile(string $filePath, string $content)
    {
        file_put_contents($filePath, $content);
    }

    /**
     * @return string[]
     */
    public function getFolders(string $dir): array
    {
        return glob("$dir/*", GLOB_ONLYDIR);
    }

    public function checkFileExists(string $filePath): bool
    {
        return is_file($filePath);
    }

    public function checkFolderExists(string $folderPath): bool
    {
        return is_dir($folderPath);
    }

    public function equals(Connection $connection): bool
    {
        return $connection instanceof LocalConnection;
    }

    public function removeFile(string $path)
    {
        unlink($path);
    }
}
