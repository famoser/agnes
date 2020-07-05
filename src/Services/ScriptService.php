<?php

namespace Agnes\Services;

use Agnes\AgnesFactory;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

class ScriptService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var AgnesFactory
     */
    private $agnesFactor;

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * ScriptService constructor.
     */
    public function __construct(StyleInterface $io, ConfigurationService $configurationService, AgnesFactory $agnesFactor)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
        $this->agnesFactor = $agnesFactor;
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    public function getBuildHookCommands(): array
    {
        $scripts = $this->configurationService->getScriptsForHook('build');

        $commands = [];
        foreach ($scripts as $name => $script) {
            if (isset($script['instance_filter'])) {
                $this->io->warning("$name script uses unsupported instance property for build hook. skipping...");
                continue;
            }

            if (isset($script['commands'])) {
                $commands = array_merge($commands, $script['commands']);
            } else {
                $this->io->warning("$name script has no commands specified. skipping...");
            }
        }

        return $commands;
    }

    /**
     * @throws Exception
     */
    public function executeDeployHook(Instance $instance, Installation $newInstallation)
    {
        $previousInstallation = $instance->getCurrentInstallation();

        $arguments = [];
        $hasPreviousInstallation = null !== $previousInstallation;
        $arguments['HAS_PREVIOUS_INSTALLATION'] = $hasPreviousInstallation ? 'true' : 'false';
        if ($hasPreviousInstallation) {
            $arguments['PREVIOUS_INSTALLATION_PATH'] = $previousInstallation->getFolder();
        }

        $this->executeScriptsForHook('deploy', $instance, $newInstallation, $arguments);
    }

    /**
     * @throws Exception
     */
    public function executeAfterDeployHook(Instance $instance)
    {
        $this->executeScriptsForHook('after_deploy', $instance, $instance->getCurrentInstallation());
    }

    /**
     * @throws Exception
     */
    public function executeRollbackHook(Instance $instance, Installation $previousInstallation)
    {
        $arguments = ['PREVIOUS_INSTALLATION_PATH' => $previousInstallation->getFolder()];

        $this->executeScriptsForHook('rollback', $instance, $instance->getCurrentInstallation(), $arguments);
    }

    /**
     * @throws Exception
     */
    public function executeAfterRollbackHook(Instance $instance)
    {
        $this->executeScriptsForHook('after_rollback', $instance, $instance->getCurrentInstallation());
    }

    /**
     * @throws Exception
     */
    private function executeScriptsForHook(string $hook, Instance $instance, Installation $installation, array $arguments = [])
    {
        $scripts = $this->configurationService->getScriptsForHook($hook);

        foreach ($scripts as $name => $script) {
            // filter by instance
            if (isset($script['instance'])) {
                $filter = Filter::createFromInstanceSpecification($script['instance']);
                if (!$filter->instanceMatches($instance)) {
                    $this->io->text($name.' script\'s filter '.$script['instance'].' does not match instance '.$instance->describe().'. skipping...');
                    continue;
                }
            }

            if (!isset($script['commands'])) {
                $this->io->warning($name.' script has no commands defined. skipping...');
                continue;
            }

            $commands = is_array($script['commands']) ? $script['commands'] : [$script['commands']];
            $this->io->text('executing commands for '.$name.'...');
            $instance->getConnection()->executeScript($installation->getFolder(), $commands, $arguments);
        }
    }
}
