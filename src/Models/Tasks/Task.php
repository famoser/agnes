<?php


namespace Agnes\Models\Tasks;

use Agnes\Services\TaskExecutionService;

abstract class Task
{
    /**
     * @var string[]
     */
    private $envVariables = [];

    /**
     * @var bool
     */
    private $clearWorkingFolder = true;

    /**
     * @var string
     */
    private $workingFolder;

    /**
     * @var string[]
     */
    private $prependCommands = [];

    /**
     * @var array
     */
    private $commands;

    /**
     * ReleaseBuildConfig constructor.
     * @param string $workingFolder
     * @param string[] $commands
     */
    public function __construct(string $workingFolder, array $commands)
    {
        $this->workingFolder = $workingFolder;
        $this->commands = $commands;
    }

    /**
     * @return string
     */
    public function getWorkingFolder(): string
    {
        return $this->workingFolder;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addEnvVariable(string $key, string $value)
    {
        $this->envVariables[$key] = $value;
    }

    /**
     * @param string $command
     */
    public function prependCommand(string $command)
    {
        $this->prependCommands[] = $command;
    }

    /**
     * @return string[]
     */
    public function getPrependCommands(): array
    {
        return $this->prependCommands;
    }

    public abstract function execute(TaskExecutionService $service);

    /**
     * @return bool
     */
    public function getClearWorkingFolder(): bool
    {
        return $this->clearWorkingFolder;
    }

    /**
     * @param bool $clearWorkingFolder
     */
    public function setClearWorkingFolder(bool $clearWorkingFolder): void
    {
        $this->clearWorkingFolder = $clearWorkingFolder;
    }

    /**
     * @return string[]
     */
    public function getEnvVariables(): array
    {
        return $this->envVariables;
    }
}