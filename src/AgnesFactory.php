<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes;

use Agnes\Commands\AgnesCommand;
use Agnes\Commands\BuildCommand;
use Agnes\Commands\ClearCommand;
use Agnes\Commands\CopyCommand;
use Agnes\Commands\DeployCommand;
use Agnes\Commands\ReleaseCommand;
use Agnes\Commands\RollbackCommand;
use Agnes\Commands\RunCommand;
use Agnes\Services\ConfigurationService;
use Agnes\Services\FileService;
use Agnes\Services\GithubService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\ScriptService;
use Agnes\Services\TaskService;
use Symfony\Component\Console\Style\OutputStyle;

class AgnesFactory
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * AgnesFactory constructor.
     */
    public function __construct(OutputStyle $io)
    {
        // construct internal services
        $configurationService = new ConfigurationService($io);
        $fileService = new FileService($io, $configurationService);
        $githubService = new GithubService($io, $configurationService);
        $installationService = new InstallationService($io, $configurationService);
        $instanceService = new InstanceService($io, $configurationService, $installationService);
        $scriptService = new ScriptService($io, $configurationService);
        $taskService = new TaskService($io, $configurationService, $fileService, $githubService, $installationService, $instanceService, $scriptService);

        // set properties
        $this->configurationService = $configurationService;
        $this->taskService = $taskService;
    }

    public function getConfigurationService(): ConfigurationService
    {
        return $this->configurationService;
    }

    public function getTaskService(): TaskService
    {
        return $this->taskService;
    }

    /**
     * @return AgnesCommand[]
     */
    public static function getCommands(): array
    {
        return [
            new BuildCommand(),
            new ClearCommand(),
            new CopyCommand(),
            new DeployCommand(),
            new ReleaseCommand(),
            new RollbackCommand(),
            new RunCommand(),
        ];
    }
}
