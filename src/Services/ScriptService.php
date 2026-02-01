<?php

namespace Agnes\Services;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\Configuration\Script;
use Symfony\Component\Console\Style\StyleInterface;

class ScriptService
{
    /**
     * ScriptService constructor.
     */
    public function __construct(private StyleInterface $io, private ConfigurationService $configurationService)
    {
    }

    /**
     * @return string[]
     *
     *
     */
    public function getBuildHookCommands(): array
    {
        $scripts = $this->configurationService->getScriptsForHook('build');

        $commands = [];
        foreach ($scripts as $script) {
            if (null !== $script->getFilter()) {
                $this->io->warning($script->getName() . ' script defines a filter which is unsupported for build hook. skipping...');
                continue;
            }

            $commands = array_merge($commands, $script->getScript());
        }

        return $commands;
    }

    /**
     *
     */
    public function executeDeployHook(Instance $instance, Installation $newInstallation): void
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
     *
     */
    public function executeAfterDeployHook(Instance $instance): void
    {
        $this->executeScriptsForHook('after_deploy', $instance, $instance->getCurrentInstallation());
    }

    /**
     *
     */
    public function executeRollbackHook(Instance $instance, Installation $previousInstallation): void
    {
        $arguments = ['PREVIOUS_INSTALLATION_PATH' => $previousInstallation->getFolder()];

        $this->executeScriptsForHook('rollback', $instance, $instance->getCurrentInstallation(), $arguments);
    }

    /**
     *
     */
    public function executeAfterRollbackHook(Instance $instance): void
    {
        $this->executeScriptsForHook('after_rollback', $instance, $instance->getCurrentInstallation());
    }

    /**
     *
     */
    private function executeScriptsForHook(string $hook, Instance $instance, Installation $installation, array $arguments = []): void
    {
        $scripts = $this->configurationService->getScriptsForHook($hook);

        $this->executeScripts($scripts, $instance, $installation, $arguments);
    }

    /**
     *
     */
    public function executeScriptByName(Instance $target, Installation $installation, string $name): void
    {
        $script = $this->configurationService->getScriptByName($name);

        $this->executeScripts([$script], $target, $installation);
    }

    /**
     * @param Script[] $scripts
     *
     *
     */
    private function executeScripts(array $scripts, Instance $instance, Installation $installation, array $arguments = []): void
    {
        foreach ($scripts as $script) {
            if (null !== $script->getFilter() && !$script->getFilter()->instanceMatches($instance)) {
                continue;
            }

            $this->io->text('executing script ' . $script->getName());
            $instance->getConnection()->executeScript($installation->getFolder(), $script->getScript(), $arguments);
        }
    }
}
