<?php


namespace Agnes\Models\Tasks;

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
     * ReleaseBuildConfig constructor.
     * @param string[] $commands
     */
    public function __construct(array $commands)
    {
        $this->commands = $commands;
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
}