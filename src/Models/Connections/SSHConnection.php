<?php


namespace Agnes\Models\Connections;


use Agnes\Models\Tasks\Task;
use Agnes\Services\FileService;
use Agnes\Services\TaskService;

class SSHConnection extends Connection
{
    /**
     * @var string
     */
    private $destination;

    /**
     * SSHConnection constructor.
     * @param string $workingFolder
     * @param string $destination
     */
    public function __construct(string $workingFolder, string $destination)
    {
        parent::__construct($workingFolder);

        $this->destination = $destination;
    }

    /**
     * @param Task $task
     * @param TaskService $service
     * @throws \Exception
     */
    public function executeTask(Task $task, TaskService $service)
    {
        $service->executeSSH($this, $task);
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $filePath
     * @param FileService $fileService
     * @return string
     */
    public function readFile(string $filePath, FileService $fileService): string
    {
        return $fileService->readSSH($this, $filePath);
    }

    /**
     * @param string $filePath
     * @param string $content
     * @param FileService $fileService
     */
    public function writeFile(string $filePath, string $content, FileService $fileService)
    {
        $fileService->writeSSH($this, $filePath, $content);
    }
}