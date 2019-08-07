<?php


namespace Agnes\Models\Tasks;


use Agnes\Services\TaskExecutionService;

class SSHTask extends Task
{
    /**
     * @var string
     */
    private $destination;

    public function __construct(string $workingFolder, array $commands, string $destination)
    {
        parent::__construct($workingFolder, $commands);

        $this->destination = $destination;
    }

    /**
     * @param TaskExecutionService $service
     * @throws \Exception
     */
    public function execute(TaskExecutionService $service)
    {
        $service->executeSSH($this);
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }
}