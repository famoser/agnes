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

class LocalConnection extends Connection
{
    /**
     * @throws \Exception
     */
    protected function executeWithinWorkingFolder(string $workingFolder, array $commands): void
    {
        // change working directory
        $originWorkingFolder = getcwd();
        \chdir($workingFolder);

        // execute commands
        $this->executeCommands($commands);

        // recover working directory
        \chdir($originWorkingFolder);
    }

    public function readFile(string $filePath): string
    {
        return \file_get_contents($filePath);
    }

    public function writeFile(string $filePath, string $content): void
    {
        $folder = dirname($filePath);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        \file_put_contents($filePath, $content);
    }

    /**
     * @return string[]
     */
    public function getFolders(string $dir): array
    {
        $fullPaths = \glob("$dir/*", \GLOB_ONLYDIR);

        return $this->keepFolderOnly($fullPaths);
    }

    public function checkFileExists(string $filePath): bool
    {
        return \is_file($filePath);
    }

    public function checkSymlinkExists(string $symlinkPath): bool
    {
        return is_link($symlinkPath);
    }

    public function checkFolderExists(string $folderPath): bool
    {
        return is_dir($folderPath);
    }

    public function equals(Connection $connection): bool
    {
        return $connection instanceof LocalConnection;
    }

    public function removeFile(string $path): void
    {
        unlink($path);
    }
}
