<?php

namespace Agnes\Services;

use Agnes\Actions\CopySharedAction;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class ScriptService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var CopySharedAction
     */
    private $copySharedAction;

    /**
     * ScriptService constructor.
     */
    public function __construct(ConfigurationService $configurationService, CopySharedAction $copySharedAction)
    {
        $this->configurationService = $configurationService;
        $this->copySharedAction = $copySharedAction;
    }

    /**
     * @return string[]
     *
     * @throws \Exception
     */
    public function getBuildHookCommands(OutputInterface $output): array
    {
        $scripts = $this->configurationService->getScriptsForHook('build');

        $commands = [];
        foreach ($scripts as $name => $script) {
            if (isset($script['instances']) || isset($script['action'])) {
                $output->writeln("$name script uses unsupported instance or action property for build hook. skipping...");
                continue;
            }

            if (isset($script['commands'])) {
                $commands = array_merge($commands, $script['commands']);
            } else {
                $output->writeln("$name script has no commands specified. skipping...");
            }
        }

        return $commands;
    }

    public function executeDeployHook(OutputInterface $output, Instance $instance, Installation $newInstallation)
    {
        $previousInstallation = $instance->getCurrentInstallation();

        $arguments = [];
        $hasPreviousInstallation = null !== $previousInstallation;
        $arguments['HAS_PREVIOUS_INSTALLATION'] = $hasPreviousInstallation ? 'true' : 'false';
        if ($hasPreviousInstallation) {
            $arguments['PREVIOUS_INSTALLATION_PATH'] = $previousInstallation->getFolder();
        }

        $this->executeDeployRollbackHooks($output, 'deploy', $instance, $newInstallation, $arguments);
    }

    public function executeAfterDeployHook(OutputInterface $output, Instance $instance)
    {
        $this->executeDeployRollbackHooks($output, 'after_deploy', $instance, $instance->getCurrentInstallation());
    }

    public function executeRollbackHook(OutputInterface $output, Instance $instance, Installation $previousInstallation)
    {
        $arguments = ['PREVIOUS_INSTALLATION_PATH' => $previousInstallation->getFolder()];

        $this->executeDeployRollbackHooks($output, 'rollback', $instance, $instance->getCurrentInstallation(), $arguments);
    }

    public function executeAfterRollbackHook(OutputInterface $output, Instance $instance)
    {
        $this->executeDeployRollbackHooks($output, 'after_rollback', $instance, $instance->getCurrentInstallation());
    }

    /**
     * @throws Exception
     */
    private function executeDeployRollbackHooks(OutputInterface $output, string $hook, Instance $instance, Installation $installation, array $arguments = [])
    {
        $scripts = $this->configurationService->getScriptsForHook($hook);

        foreach ($scripts as $name => $script) {
            // filter by instance
            if (isset($script['instance'])) {
                $filter = Filter::createFromInstanceSpecification($script['instance']);
                if (!$filter->instanceMatches($instance)) {
                    $output->writeln($name.' script\'s filter '.$script['instance'].' does not match instance '.$instance->describe().'. skipping...');
                    continue;
                }
            }

            if (isset($script['commands'])) {
                $commands = $script['commands'];
                $output->writeln('executing commands for '.$name.'...');
                $instance->getConnection()->executeScript($installation->getFolder(), $commands, $arguments);
            } elseif (isset($script['action'])) {
                $arguments = isset($script['arguments']) ? $script['arguments'] : [];
                $output->writeln('executing action for '.$name.'...');
                $this->executeAction($output, $script['action'], $arguments, $instance);
            } else {
                $output->writeln($name.'script has no command or action defined. skipping...');
            }
        }
    }

    /**
     * @throws Exception
     */
    private function executeAction(OutputInterface $output, string $action, array $arguments, Instance $instance)
    {
        if ('copy:shared' !== $action) {
            $output->writeln('only copy:shared action supported');

            return;
        }

        if (!isset($arguments['source'])) {
            $output->writeln('must specify source argument for copy:shared action like arguments: { source: production }');

            return;
        }

        $source = $arguments['source'];
        $copyShared = $this->copySharedAction->createSingle($instance, $source, $output);
        if (null === $copyShared) {
            return;
        }

        if (!$this->copySharedAction->canExecute($copyShared, $output)) {
            $output->writeln('execution of "'.$copyShared->describe().'" blocked by policy; skipping');
        } else {
            $this->copySharedAction->execute($copyShared, $output);
        }
    }
}
