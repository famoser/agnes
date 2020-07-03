<?php

namespace Agnes\Actions;

use Agnes\Models\Filter;
use Agnes\Models\Instance;
use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractAction
{
    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * AbstractAction constructor.
     */
    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    public function execute(AbstractPayload $payload, OutputInterface $output)
    {
        $this->doExecute($payload, $output);
    }

    /**
     * @throws Exception
     */
    public function canExecute(AbstractPayload $payload, OutputInterface $output): bool
    {
        if (!$this->canProcessPayload($payload, $output)) {
            return false;
        }

        if (!$payload->canExecute($this->policyService, $output)) {
            return false;
        }

        return true;
    }

    /**
     * @return string[]
     *
     * @throws \Exception
     */
    protected function getBuildHookCommands(OutputInterface $output): array
    {
        $scripts = $this->configurationService->getScripts('build');

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

    /**
     * @throws Exception
     */
    protected function executeDeployAndRollbackHooks(OutputInterface $output, string $hook, Instance $instance, array $arguments = [])
    {
        $scripts = $this->configurationService->getScripts($hook);

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
                $instance->getConnection()->executeScript($instance->getCurrentInstallation()->getFolder(), $commands, $arguments);
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

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param $payload
     */
    abstract protected function canProcessPayload($payload, OutputInterface $output): bool;

    /**
     * @param $payload
     */
    abstract protected function doExecute($payload, OutputInterface $output);
}
