<?php


namespace Agnes\Models\Connections;


use Agnes\Models\Task;
use Agnes\Services\TaskService;
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
     * @param array $commands
     */
    public function execute(...$commands)
    {
        foreach ($commands as $command) {
            exec($command);
        }
    }

    /**
     * @param Task $task
     * @param TaskService $service
     * @throws Exception
     */
    public function executeTask(Task $task, TaskService $service)
    {
        $commands = $service->getCommands($task);

        // ensure working directory exists
        $workingFolderCommands = $service->ensureFolderExistsCommands($task->getWorkingFolder());
        $this->execute(...$workingFolderCommands);

        // change working directory
        chdir($task->getWorkingFolder());

        // execute commands
        $service->executeCommands($commands);
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
}