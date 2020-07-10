<?php

namespace Agnes\Services;

use Agnes\Models\Connection\Connection;
use Agnes\Models\Connection\LocalConnection;
use Agnes\Models\Connection\SSHConnection;
use Agnes\Models\Executor\BSDExecutor;
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
use Symfony\Component\Console\Style\StyleInterface;
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
    private $configFolder = null;

    const AGNES_VERSION = 3;

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * ConfigurationService constructor.
     */
    public function __construct(StyleInterface $io)
    {
        $this->io = $io;
    }

    public function validate(): bool
    {
        if (0 === count($this->config)) {
            $this->io->error('no config supplied');

            return false;
        }

        $version = $this->getNestedConfig('agnes', 'version');
        if (self::AGNES_VERSION !== $version) {
            $this->io->error('expected '.$version.' as the agnes.version value');

            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function addConfig(string $path)
    {
        $configFileContent = file_get_contents($path);
        $config = Yaml::parse($configFileContent);

        $this->replaceEnvVariables($config);

        $this->config = array_merge_recursive($this->config, $config);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function getRepositoryCloneUrl()
    {
        $repository = $this->getNestedConfig('github', 'repository');

        return 'git@github.com:'.$repository;
    }

    /**
     * @return GithubConfig
     *
     * @throws Exception
     */
    public function getGithubConfig()
    {
        $apiToken = $this->getNestedConfig('github', 'api_token');
        $repository = $this->getNestedConfig('github', 'repository');

        return new GithubConfig($apiToken, $repository);
    }

    /**
     * @return Connection
     *
     * @throws Exception
     */
    public function getBuildConnection()
    {
        $connection = $this->getNestedConfig('agnes', 'build_target', 'connection');

        return $this->getConnection($connection);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function getBuildPath()
    {
        return $this->getNestedConfig('agnes', 'build_target', 'path');
    }

    /**
     * @return string|null
     *
     * @throws Exception
     */
    public function getAgnesConfigFolder()
    {
        return $this->getNestedConfigWithDefault(null, 'agnes', 'config_folder');
    }

    /**
     * @return Script[]
     *
     * @throws Exception
     */
    public function getScriptsForHook(string $hook): array
    {
        $config = $this->getNestedConfigWithDefault([], 'scripts');

        $result = [];
        foreach ($config as $name => $script) {
            if ((isset($script['hook']) && $script['hook'] !== $hook) ||
                (isset($script['hooks']) && !in_array($hook, $script['hooks']))) {
                continue;
            }

            if (!isset($script['script'])) {
                $this->io->warning('script '.$name.' is missing the required script property. skipping...');
                continue;
            }

            $commands = is_array($script['script']) ? $script['script'] : [$script['script']];
            $filter = isset($script['instance_filter']) ? Filter::createFromInstanceSpecification($script['instance_filter']) : null;

            $result[] = new Script($name, $commands, $filter);
        }

        return $result;
    }

    /**
     * @return Task[]
     *
     * @throws Exception
     */
    public function getAfterTasks(string $hook): array
    {
        $config = $this->getNestedConfigWithDefault([], 'tasks');

        $result = [];
        foreach ($config as $name => $action) {
            if ((isset($action['after']) && $action['after'] !== $hook)) {
                continue;
            }

            if (!isset($action['action'])) {
                $this->io->warning('action '.$name.' is missing the required action property. skipping...');
                continue;
            }

            $arguments = is_array($action['arguments']) ? $action['arguments'] : [];
            $filter = isset($action['instance_filter']) ? Filter::createFromInstanceSpecification($action['instance_filter']) : null;

            $result[] = new Task($name, $action['action'], $arguments, $filter);
        }

        return $result;
    }

    /**
     * @param string ...$keys
     *
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     *
     * @throws Exception
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
     * @param $default
     * @param string ...$keys
     *
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     *
     * @throws Exception
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
     * @throws Exception
     */
    private function getValue(array $source, string $key, $default = false)
    {
        if (!isset($source[$key])) {
            if (false === $default) {
                throw new Exception('key '.$key.' does not exist.');
            } else {
                return $default;
            }
        }

        return $source[$key];
    }

    /**
     * @throws Exception
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
                        throw new Exception('The requested environment value '.$envName.' is not set.');
                    }
                    $item = $_ENV[$envName];
                }
            }
        }
    }

    /**
     * @return Server[]
     *
     * @throws Exception
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
     * @throws Exception
     */
    public function getPolicies(string $type)
    {
        $policies = $this->getNestedConfigWithDefault([], 'policies', $type);

        /** @var Policy[] $parsedPolicies */
        $parsedPolicies = [];
        foreach ($policies as $policy) {
            $filter = isset($policy['filter']) ? $this->getFilter($policy['filter']) : null;

            $policyType = $policy['type'];
            switch ($policyType) {
                case 'stage_write_up':
                    $parsedPolicies[] = new StageWriteUpPolicy($filter, $policy['layers']);
                    break;
                case 'stage_write_down':
                    $parsedPolicies[] = new StageWriteDownPolicy($filter, $policy['layers']);
                    break;
                case 'same_release':
                    $parsedPolicies[] = new SameReleasePolicy($filter);
                    break;
                default:
                    throw new Exception('Unknown policy type: '.$policyType);
            }
        }

        return $parsedPolicies;
    }

    /**
     * @param string[] $filter
     *
     * @return Filter
     */
    private function getFilter(array $filter)
    {
        $servers = isset($filter['servers']) ? $filter['servers'] : [];
        $environments = isset($filter['environments']) ? $filter['environments'] : [];
        $stages = isset($filter['stages']) ? $filter['stages'] : [];

        return new Filter($servers, $environments, $stages);
    }

    /**
     * @param $connection
     *
     * @return LocalConnection|SSHConnection
     *
     * @throws Exception
     */
    private function getConnection($connection)
    {
        $connectionType = $this->getValue($connection, 'type');

        $system = $this->getValue($connection, 'system', 'Linux');
        $executor = $this->getExecutor($system);

        if ('local' === $connectionType) {
            return new LocalConnection($executor);
        } elseif ('ssh' === $connectionType) {
            $destination = $connection['destination'];

            return new SSHConnection($executor, $destination);
        } else {
            throw new Exception("unknown connection type $connectionType");
        }
    }

    /**
     * @return BSDExecutor|LinuxExecutor
     *
     * @throws Exception
     */
    private function getExecutor(string $system)
    {
        switch ($system) {
            case 'Linux':
                return new LinuxExecutor();
            case 'FreeBSD':
                return new BSDExecutor();
            default:
                throw new Exception('System not implemented: '.$system);
        }
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    public function getSharedFolders()
    {
        return $this->getNestedConfigWithDefault([], 'data', 'shared_folders');
    }

    /**
     * @return File[]
     *
     * @throws Exception
     */
    public function getFiles()
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
