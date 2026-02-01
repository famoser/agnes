<?php

namespace Agnes\Services;

use Agnes\Models\Connection\Connection;
use Agnes\Models\Connection\LocalConnection;
use Agnes\Models\Connection\SSHConnection;
use Agnes\Models\Executor\BSDExecutor;
use Agnes\Models\Executor\Executor;
use Agnes\Models\Executor\LinuxExecutor;
use Agnes\Models\Filter;
use Agnes\Models\Policy\Policy;
use Agnes\Models\Policy\SameReleasePolicy;
use Agnes\Models\Policy\StageWriteDownPolicy;
use Agnes\Models\Policy\StageWriteUpPolicy;
use Agnes\Services\Configuration\Environment;
use Agnes\Services\Configuration\File;
use Agnes\Services\Configuration\GithubConfig;
use Agnes\Services\Configuration\Script;
use Agnes\Services\Configuration\Server;
use Agnes\Services\Configuration\Task;
use Exception;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Yaml\Yaml;

class ConfigurationService
{
    private array $config = [];

    private ?string $configFolder = null;

    public const AGNES_VERSION = 4;

    public function __construct(private OutputStyle $io)
    {
    }

    public function validate(): bool
    {
        if ([] === $this->config) {
            $this->io->error('no config supplied');

            return false;
        }

        $version = $this->getNestedConfig('agnes', 'version');
        if (self::AGNES_VERSION !== (int) $version) {
            $this->io->error('expected ' . self::AGNES_VERSION . ' as the agnes.version value');

            return false;
        }

        return true;
    }

    public function addConfig(string $path): void
    {
        $configFileContent = file_get_contents($path);
        $config = Yaml::parse($configFileContent);

        $this->replaceEnvVariables($config);

        $this->config = array_merge_recursive($this->config, $config);
    }

    public function getConfigRepositoryUrl(): ?string
    {
        return $this->getNestedConfigWithDefault(null, 'config', 'repository', 'url');
    }

    public function getConfigRepositoryFolder(): ?string
    {
        return $this->getNestedConfigWithDefault(null, 'config', 'repository', 'folder');
    }

    public function getRepositoryUrl(): string
    {
        $cloneUrl = $this->getNestedConfigWithDefault(null, 'repository', 'url');
        if (null !== $cloneUrl) {
            return $cloneUrl;
        }

        $githubRepository = $this->getNestedConfigWithDefault(null, 'github', 'repository');
        if (null !== $githubRepository) {
            return 'git@github.com:' . $githubRepository;
        }

        throw new \Exception('no git clone url configured. configure repository.url to change this.');
    }

    public function getGithubConfig(): ?GithubConfig
    {
        $githubConfig = $this->getNestedConfigWithDefault(null, 'github');
        if (null === $githubConfig) {
            return null;
        }

        $apiToken = $this->getNestedConfig('github', 'api_token');
        $repository = $this->getNestedConfig('github', 'repository');

        return new GithubConfig($apiToken, $repository);
    }

    public function getBuildConnection(): ?Connection
    {
        $connection = $this->getNestedConfigWithDefault([], 'build', 'connection');

        return $this->getConnection($connection);
    }

    public function getBuildPath(): string
    {
        return $this->getNestedConfig('build', 'path');
    }

    public function getConfigPath(): ?string
    {
        return $this->getNestedConfigWithDefault(null, 'config', 'path');
    }

    /**
     * @return Script[]
     */
    public function getScriptsForHook(string $hook): array
    {
        return $this->getScriptsByCondition(function (string $name, array $script) use ($hook): bool {
            return (isset($script['hook']) && $script['hook'] === $hook)
                || isset($script['hooks']) && in_array($hook, $script['hooks']);
        });
    }

    public function getScriptByName(string $name): ?Script
    {
        $scripts = $this->getScriptsByCondition(function (string $scriptName, array $script) use ($name): bool {
            return $scriptName === $name;
        });

        if ([] === $scripts) {
            $this->io->warning('script ' . $name . ' does not exist.');

            return null;
        }

        return $scripts[0];
    }

    /**
     * @return Script[]
     */
    private function getScriptsByCondition(callable $condition): array
    {
        $config = $this->getNestedConfigWithDefault([], 'scripts');

        $result = [];
        foreach ($config as $name => $script) {
            if (!$condition($name, $script)) {
                continue;
            }

            if (!isset($script['script'])) {
                $this->io->warning('script ' . $name . ' is missing the required script property. skipping...');
                continue;
            }

            $commands = is_array($script['script']) ? $script['script'] : [$script['script']];
            $filter = isset($script['instance_filter']) ? Filter::createFromInstanceSpecification($script['instance_filter']) : null;
            $order = isset($script['order']) ? (int) $script['order'] : 0;

            if (!isset($result[$order])) {
                $result[$order] = [];
            }

            $result[$order][] = new Script($name, $commands, $filter);
        }

        ksort($result);

        return array_merge(...$result);
    }

    /**
     * @return Task[]
     */
    public function getBeforeTasks(string $task): array
    {
        return $this->getTasksByCondition(function (string $name, array $taskConfig) use ($task): bool {
            return isset($taskConfig['before']) && $taskConfig['before'] === $task;
        });
    }

    /**
     * @return Task[]
     */
    public function getAfterTasks(string $task): array
    {
        return $this->getTasksByCondition(function (string $name, array $taskConfig) use ($task): bool {
            return isset($taskConfig['after']) && $taskConfig['after'] === $task;
        });
    }

    /**
     * @return Task[]
     */
    private function getTasksByCondition(callable $condition): array
    {
        $config = $this->getNestedConfigWithDefault([], 'tasks');

        $result = [];
        foreach ($config as $name => $task) {
            if (!$condition($name, $task)) {
                continue;
            }

            if (!isset($task['task'])) {
                $this->io->warning('task ' . $name . ' is missing the required task property. skipping...');
                continue;
            }

            $arguments = is_array($task['arguments']) ? $task['arguments'] : [];
            $filter = isset($task['instance_filter']) ? Filter::createFromInstanceSpecification($task['instance_filter']) : null;

            $result[] = new Task($name, $task['task'], $arguments, $filter);
        }

        return $result;
    }

    /**
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     */
    private function getNestedConfig(string ...$keys)
    {
        $current = $this->config;

        foreach ($keys as $key) {
            $current = $this->getValue($current, $key);
        }

        return $current;
    }

    /**
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     */
    private function getNestedConfigWithDefault(?array $default, string ...$keys)
    {
        // choose new default 2 because if passed "false" to geValue this throws exception if not found
        $defaultIsFalse = false === $default;
        if ($defaultIsFalse) {
            $default = 2;
        }

        $current = $this->config;

        foreach ($keys as $key) {
            $current = $this->getValue($current, $key, $default);
            if ($current === $default) {
                break;
            }
        }

        if ($current === $default && $defaultIsFalse) {
            return false;
        }

        return $current;
    }

    /**
     * @param bool|string $default
     *
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     */
    private function getValue(array $source, string $key, mixed $default = false)
    {
        if (!isset($source[$key])) {
            if (false === $default) {
                throw new \Exception('key ' . $key . ' does not exist.');
            }

            return $default;
        }

        return $source[$key];
    }

    private function replaceEnvVariables(array &$config): void
    {
        foreach ($config as &$item) {
            if (is_array($item)) {
                $this->replaceEnvVariables($item);
            } elseif (str_starts_with($item, '%env(')) {
                $envPart = substr($item, 5);
                if (0 === substr_compare($envPart, ')%', -2)) {
                    $envName = substr($envPart, 0, -2);
                    if (!isset($_ENV[$envName])) {
                        throw new \Exception('The requested environment value ' . $envName . ' is not set.');
                    }
                    $item = $_ENV[$envName];
                }
            }
        }
    }

    /**
     * @return Server[]
     */
    public function getServers(): array
    {
        $serverConfigs = $this->getNestedConfigWithDefault([], 'servers');

        $servers = [];
        foreach ($serverConfigs as $serverName => $serverConfig) {
            $connectionConfig = $this->getValue($serverConfig, 'connection');
            $connection = $this->getConnection($connectionConfig);
            $path = $this->getValue($serverConfig, 'path');
            $keepInstallations = $this->getValue($serverConfig, 'keep_installations', 2);
            $scriptOverrides = $this->getValue($serverConfig, 'script_overrides', []);

            $environments = [];
            foreach ($serverConfig['environments'] as $environmentName => $stages) {
                $environments[] = new Environment($environmentName, $stages);
            }

            $servers[] = new Server($serverName, $connection, $path, $keepInstallations, $scriptOverrides, $environments);
        }

        return $servers;
    }

    /**
     * @return Policy[]
     */
    public function getPoliciesForTask(string $task): array
    {
        $policies = $this->getNestedConfigWithDefault([], 'policies');

        /** @var Policy[] $parsedPolicies */
        $parsedPolicies = [];
        foreach ($policies as $name => $policy) {
            $filter = isset($policy['instance_filter']) ? Filter::createFromInstanceSpecification($policy['instance_filter']) : null;

            if (!isset($policy['task'])) {
                $this->io->warning('policy ' . $name . ' is missing the required task property. skipping...');
                continue;
            }

            if ($policy['task'] !== $task) {
                continue;
            }

            $policyType = $policy['type'];
            $parsedPolicies[] = match ($policyType) {
                'stage_write_up' => new StageWriteUpPolicy($name, $filter, $policy['layers']),
                'stage_write_down' => new StageWriteDownPolicy($name, $filter, $policy['layers']),
                'same_release' => new SameReleasePolicy($name, $filter),
                default => throw new \Exception('Policy ' . $name . ' has unknown policy type ' . $policyType . '.'),
            };
        }

        return $parsedPolicies;
    }

    /**
     * @param string[] $connection
     *
     *
     */
    private function getConnection(array $connection): Connection
    {
        $system = $this->getValue($connection, 'system', 'Linux');
        $executor = $this->getExecutor($system);

        $connectionType = $this->getValue($connection, 'type', 'local');
        if ('local' === $connectionType) {
            return new LocalConnection($this->io, $executor);
        }
        if ('ssh' === $connectionType) {
            $destination = $connection['destination'];
            return new SSHConnection($this->io, $executor, $destination);
        }
        throw new \Exception("unknown connection type $connectionType");
    }

    private function getExecutor(string $system): Executor
    {
        return match ($system) {
            'Linux' => new LinuxExecutor(),
            'FreeBSD' => new BSDExecutor(),
            default => throw new \Exception('System not implemented: ' . $system),
        };
    }

    /**
     * @return string[]
     */
    public function getSharedFolders(): array
    {
        return $this->getNestedConfigWithDefault([], 'data', 'shared_folders');
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        $entries = $this->getNestedConfigWithDefault([], 'data', 'files');

        /** @var File[] $files */
        $files = [];
        foreach ($entries as $entry) {
            $files[] = new File((bool) $entry['required'], $entry['path']);
        }

        return $files;
    }

    public function getConfigFolder(): ?string
    {
        return $this->configFolder;
    }

    public function setConfigFolder(string $configFolder): void
    {
        $this->configFolder = $configFolder;
    }
}
