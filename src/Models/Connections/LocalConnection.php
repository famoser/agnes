<?php


namespace Agnes\Models\Connections;


use Agnes\Models\Tasks\Task;
use Agnes\Services\FileService;
use Agnes\Services\TaskService;

class LocalConnection extends Connection
{
    /**
     * @param Task $task
     * @param TaskService $service
     * @throws \Exception
     */
    public function executeTask(Task $task, TaskService $service)
    {
        $service->executeLocal($this, $task);
    }

    /**
     * @param string $filePath
     * @param FileService $fileService
     * @return string
     */
    public function readFile(string $filePath, FileService $fileService): string
    {
        return $fileService->readFileLocal($filePath);
    }

    /**
     * @param string $filePath
     * @param string $content
     * @param FileService $fileService
     */
    public function writeFile(string $filePath, string $content, FileService $fileService)
    {
        $fileService->writeFileLocal($filePath, $content);
    }

    /**
     * @param string $dir
     * @return string[]
     */
    public function getFolders(string $dir, FileService $fileService): array
    {
        return $fileService->getFoldersLocal($dir);
    }
}