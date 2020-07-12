<?php

namespace Agnes\Services;

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
     * @var StyleInterface
     */
    private $io;

    /**
     * ScriptService constructor.
     */
    public function __construct(StyleInterface $io, ConfigurationService $configurationService)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
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
        foreach ($scripts as $script) {
            if (null !== $script->getFilter()) {
                $this->io->warning($script->getName().' script defines a filter which is unsupported for build hook. skipping...');
                continue;
            }

            $commands = array_merge($commands, $script->getScript());
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

        $this->executeScripts($scripts, $instance, $installation, $arguments);
    }

    public function executeScriptByName(Instance $target, Installation $installation, string $name)
    {
        $scripts = $this->configurationService->getScriptByName($name);

        $this->executeScriptsForHook('after_rollback', $instance, $instance->getCurrentInstallation());
    }

    /**
     * @throws Exception
     */
    private function executeScripts(array $scripts, Instance $instance, Installation $installation, array $arguments): void
    {
        foreach ($scripts as $script) {
            // filter by instance
            if (isset($script['instance'])) {
                $filter = Filter::createFromInstanceSpecification($script['instance']);
                if (!$filter->instanceMatches($instance)) {
                    $this->io->text($script->getName().' script\'s filter '.$script['instance'].' does not match instance '.$instance->describe().'. skipping...');
                    continue;
                }
            }

            $this->io->text('executing script for '.$script->getName().'...');
            $instance->getConnection()->executeScript($installation->getFolder(), $script->getScript(), $arguments);
        }
    }
}
