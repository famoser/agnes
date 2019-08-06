<?php


namespace Agnes\Services\Configuration;


class TaskConfig
{
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
}