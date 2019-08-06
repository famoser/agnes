<?php


namespace Agnes\Services\Configuration;


class TaskConfig
{
    /**
     * @var string
     */
    private $workingFolder;

    /**
     * @var array
     */
    private $script;

    /**
     * ReleaseBuildConfig constructor.
     * @param string $workingFolder
     * @param string[] $script
     */
    public function __construct(string $workingFolder, array $script)
    {
        $this->workingFolder = $workingFolder;
        $this->script = $script;
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
    public function getScript(): array
    {
        return $this->script;
    }
}