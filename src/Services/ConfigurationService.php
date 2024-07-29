<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var string|null
     */
    private $configFolder;

    public const AGNES_VERSION = 4;

    /**
     * @var OutputStyle
     */
    private $io;

    /**
     * ConfigurationService constructor.
     */
    public function __construct(OutputStyle $io)
    {
        $this->io = $io;
    }

    /**
     * @throws \Exception
     */
    public function validate(): bool
    {
        if (0 === count($this->config)) {
            $this->io->error('no config supplied');

            return false;
        }

        $version = $this->getNestedConfig('agnes', 'version');
        if (self::AGNES_VERSION !== $version) {
            $this->io->error('expected '.self::AGNES_VERSION.' as the agnes.version value');

            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function addConfig(string $path): void
    {
        $configFileContent = file_get_contents($path);
        $config = Yaml::parse($configFileContent);

        $this->replaceEnvVariables($config);

        $this->config = array_merge_recursive($this->config, $config);
    }

    /**
     * @throws \Exception
     */
    public function getConfigRepositoryUrl(): ?string
    {
        return $this->getNestedConfigWithDefault(null, 'config', 'repository', 'url');
    }

    /**
     * @throws \Exception
     */
    public function getRepositoryUrl(): string
    {
        $cloneUrl = $this->getNestedConfigWithDefault(null, 'repository', 'url');
        if (null !== $cloneUrl) {
            return $cloneUrl;
        }

        $githubRepository = $this->getNestedConfigWithDefault(null, 'github', 'repository');
        if (null !== $githubRepository) {
            return 'git@github.com:'.$githubRepository;
        }

        throw new \Exception('no git clone url configured. configure repository.url to change this.');
    }

    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
    public function getBuildConnection(): ?Connection
    {
        $connection = $this->getNestedConfigWithDefault([], 'build', 'connection');

        return $this->getConnection($connection);
    }

    /**
     * @throws \Exception
     */
    public function getBuildPath(): string
    {
        return $this->getNestedConfig('build', 'path');
    }

    /**
     * @throws \Exception
     */
    public function getConfigPath(): ?string
    {
        return $this->getNestedConfigWithDefault(null, 'config', 'path');
    }

    /**
     * @return Script[]
     *
     * @throws \Exception
     */
    public function getScriptsForHook(string $hook): array
    {
        return $this->getScriptsByCondition(function (string $name, array $script) use ($hook) {
            return (isset($script['hook']) && $script['hook'] === $hook)
                || isset($script['hooks']) && in_array($hook, $script['hooks']);
        });
    }

    /**
     * @return Script[]
     *
     * @throws \Exception
     */
    public function getScriptByName(string $name): ?Script
    {
        $scripts = $this->getScriptsByCondition(function (string $scriptName, array $script) use ($name) {
            return $scriptName === $name;
        });

        if (0 === count($scripts)) {
            $this->io->warning('script '.$name.' does not exist.');

            return null;
        }

        return $scripts[0];
    }

    /**
     * @return Script[]
     *
     * @throws \Exception
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
                $this->io->warning('script '.$name.' is missing the required script property. skipping...');
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
     *
     * @throws \Exception
     */
    public function getBeforeTasks(string $task): array
    {
        return $this->getTasksByCondition(function (string $name, array $taskConfig) use ($task) {
            return isset($taskConfig['before']) && $taskConfig['before'] === $task;
        });
    }

    /**
     * @return Task[]
     *
     * @throws \Exception
     */
    public function getAfterTasks(string $task): array
    {
        return $this->getTasksByCondition(function (string $name, array $taskConfig) use ($task) {
            return isset($taskConfig['after']) && $taskConfig['after'] === $task;
        });
    }

    /**
     * @return Task[]
     *
     * @throws \Exception
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
                $this->io->warning('task '.$name.' is missing the required task property. skipping...');
                continue;
            }

            $arguments = is_array($task['arguments']) ? $task['arguments'] : [];
            $filter = isset($task['instance_filter']) ? Filter::createFromInstanceSpecification($task['instance_filter']) : null;

            $result[] = new Task($name, $task['task'], $arguments, $filter);
        }

        return $result;
    }

    /**
     * @param string ...$keys
     *
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     *
     * @throws \Exception
     */
    private function getNestedConfig(...$keys)
    {
        $current = $this->config;

        foreach ($keys as $key) {
            $current = $this->getValue($current, $key);
        }

        return $current;
    }

    /**
     * @param string ...$keys
     *
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     *
     * @throws \Exception
     */
    private function getNestedConfigWithDefault($default, ...$keys)
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
     * @param bool $default
     *
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     *
     * @throws \Exception
     */
    private function getValue(array $source, string $key, $default = false)
    {
        if (!isset($source[$key])) {
            if (false === $default) {
                throw new \Exception('key '.$key.' does not exist.');
            } else {
                return $default;
            }
        }

        return $source[$key];
    }

    /**
     * @throws \Exception
     */
    private function replaceEnvVariables(array &$config)
    {
        foreach ($config as &$item) {
            if (is_array($item)) {
                $this->replaceEnvVariables($item);
            } elseif (0 === strpos($item, '%env(')) {
                $envPart = substr($item, 5);
                if (0 === substr_compare($envPart, ')%', -2)) {
                    $envName = substr($envPart, 0, -2);
                    if (!isset($_ENV[$envName])) {
                        throw new \Exception('The requested environment value '.$envName.' is not set.');
                    }
                    $item = $_ENV[$envName];
                }
            }
        }
    }

    /**
     * @return Server[]
     *
     * @throws \Exception
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
     *
     * @throws \Exception
     */
    public function getPoliciesForTask(string $task): array
    {
        $policies = $this->getNestedConfigWithDefault([], 'policies');

        /** @var Policy[] $parsedPolicies */
        $parsedPolicies = [];
        foreach ($policies as $name => $policy) {
            $filter = isset($policy['filter']) ? $this->getFilter($policy['filter']) : null;

            if (!isset($policy['task'])) {
                $this->io->warning('policy '.$name.' is missing the required task property. skipping...');
                continue;
            }

            if ($policy['task'] !== $task) {
                continue;
            }

            $policyType = $policy['type'];
            switch ($policyType) {
                case 'stage_write_up':
                    $parsedPolicies[] = new StageWriteUpPolicy($name, $filter, $policy['layers']);
                    break;
                case 'stage_write_down':
                    $parsedPolicies[] = new StageWriteDownPolicy($name, $filter, $policy['layers']);
                    break;
                case 'same_release':
                    $parsedPolicies[] = new SameReleasePolicy($name, $filter);
                    break;
                default:
                    throw new \Exception('Policy '.$name.' has unknown policy type '.$policyType.'.');
            }
        }

        return $parsedPolicies;
    }

    /**
     * @param string[] $filter
     */
    private function getFilter(array $filter): Filter
    {
        $servers = is_array($filter['servers']) ? $filter['servers'] : [];
        $environments = is_array($filter['environments']) ? $filter['environments'] : [];
        $stages = is_array($filter['stages']) ? $filter['stages'] : [];

        return new Filter($servers, $environments, $stages);
    }

    /**
     * @param string[] $connection
     *
     * @throws \Exception
     */
    private function getConnection(array $connection): Connection
    {
        $system = $this->getValue($connection, 'system', 'Linux');
        $executor = $this->getExecutor($system);

        $connectionType = $this->getValue($connection, 'type', 'local');
        if ('local' === $connectionType) {
            return new LocalConnection($this->io, $executor);
        } elseif ('ssh' === $connectionType) {
            $destination = $connection['destination'];

            return new SSHConnection($this->io, $executor, $destination);
        } else {
            throw new \Exception("unknown connection type $connectionType");
        }
    }

    /**
     * @throws \Exception
     */
    private function getExecutor(string $system): Executor
    {
        switch ($system) {
            case 'Linux':
                return new LinuxExecutor();
            case 'FreeBSD':
                return new BSDExecutor();
            default:
                throw new \Exception('System not implemented: '.$system);
        }
    }

    /**
     * @return string[]
     *
     * @throws \Exception
     */
    public function getSharedFolders(): array
    {
        return $this->getNestedConfigWithDefault([], 'data', 'shared_folders');
    }

    /**
     * @return File[]
     *
     * @throws \Exception
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

    public function setConfigFolder(string $configFolder)
    {
        $this->configFolder = $configFolder;
    }
}
