<?php


namespace Agnes\Models;

class Task
{
    /**
     * @var string[]
     */
    private $envVariables = [];

    /**
     * @var string[]
     */
    private $preCommands = [];

    /**
     * @var string[]
     */
    private $commands;

    /**
     * @var string[]
     */
    private $postCommands = [];

    /**
     * @var string
     */
    private $workingFolder;

    /**
     * ReleaseBuildConfig constructor.
     * @param string $workingFolder
     * @param string[] $commands
     * @param array $envVariables
     */
    public function __construct(string $workingFolder, array $commands, array $envVariables = [])
    {
        $this->workingFolder = $workingFolder;
        $this->commands = $commands;
        $this->envVariables = $envVariables;
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
    public function addPreCommand(string $command)
    {
        $this->preCommands[] = $command;
    }

    /**
     * @param string $command
     */
    public function addPostCommand(string $command)
    {
        $this->postCommands[] = $command;
    }

    /**
     * @return string[]
     */
    public function getPreCommands(): array
    {
        return $this->preCommands;
    }

    /**
     * @return string[]
     */
    public function getEnvVariables(): array
    {
        return $this->envVariables;
    }

    /**
     * @return string[]
     */
    public function getPostCommands(): array
    {
        return $this->postCommands;
    }

    /**
     * @return string
     */
    public function getWorkingFolder(): string
    {
        return $this->workingFolder;
    }
}