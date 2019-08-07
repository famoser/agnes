<?php


namespace Agnes\Models\Connections;


use Agnes\Models\Tasks\Task;
use Agnes\Services\FileService;
use Agnes\Services\TaskService;

abstract class Connection
{
    /**
     * @var string
     */
    private $workingFolder;

    /**
     * Connection constructor.
     * @param string $workingFolder
     */
    public function __construct(string $workingFolder)
    {
        $this->workingFolder = $workingFolder;
    }

    /**
     * @param Task $task
     * @param TaskService $service
     */
    public abstract function executeTask(Task $task, TaskService $service);

    /**
     * @param string $filePath
     * @param FileService $fileService
     * @return string
     */
    public abstract function readFile(string $filePath, FileService $fileService): string;

    /**
     * @param string $filePath
     * @param string $content
     * @param FileService $fileService
     */
    public abstract function writeFile(string $filePath, string $content, FileService $fileService);

    /**
     * @return string
     */
    public function getWorkingFolder(): string
    {
        return $this->workingFolder;
    }
}