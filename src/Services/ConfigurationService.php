<?php

namespace Agnes\Services;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Connections\LocalConnection;
use Agnes\Models\Connections\SSHConnection;
use Agnes\Models\Executors\BSDExecutor;
use Agnes\Models\Executors\LinuxExecutor;
use Agnes\Models\Filter;
use Agnes\Models\Policies\Policy;
use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Agnes\Models\Policies\SameReleasePolicy;
use Agnes\Models\Policies\StageWriteDownPolicy;
use Agnes\Models\Policies\StageWriteUpPolicy;
use Agnes\Services\Configuration\Environment;
use Agnes\Services\Configuration\File;
use Agnes\Services\Configuration\GithubConfig;
use Agnes\Services\Configuration\Server;
use Exception;
use Symfony\Component\Yaml\Yaml;

class ConfigurationService
{
    /**
     * @var array
     */
    private $config = [];

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
        $repository = $this->getNestedConfig(['github', 'repository']);

        return 'git@github.com:'.$repository;
    }

    /**
     * @return GithubConfig
     *
     * @throws Exception
     */
    public function getGithubConfig()
    {
        $apiToken = $this->getNestedConfig(['github', 'api_token']);
        $repository = $this->getNestedConfig(['github', 'repository']);

        return new GithubConfig($apiToken, $repository);
    }

    /**
     * @return Connection
     *
     * @throws Exception
     */
    public function getBuildConnection()
    {
        $connection = $this->getNestedConfig(['agnes', 'build_target', 'connection']);

        return $this->getConnection($connection);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function getBuildPath()
    {
        return $this->getNestedConfig(['agnes', 'build_target', 'path']);
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    public function getScripts(string $task)
    {
        return $this->getNestedConfigWithDefault([], 'application', 'scripts', $task);
    }

    /**
     * @param string ...$keys
     *
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     *
     * @throws Exception
     */
    private function getNestedConfig(array $keys)
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
            $keepReleases = $this->getValue($serverConfig, 'keep_releases', 2);
            $scriptOverrides = $this->getValue($serverConfig, 'script_overrides', []);

            $environments = [];
            foreach ($serverConfig['environments'] as $environmentName => $stages) {
                $environments[] = new Environment($environmentName, $stages);
            }

            $servers[] = new Server($serverName, $connection, $path, $keepReleases, $scriptOverrides, $environments);
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
                case 'release_whitelist':
                    $parsedPolicies[] = new ReleaseWhitelistPolicy($filter, $policy['commitishes']);
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
        return $this->getNestedConfigWithDefault([], 'application', 'shared_folders');
    }

    /**
     * @return File[]
     *
     * @throws Exception
     */
    public function getFiles()
    {
        $entries = $this->getNestedConfigWithDefault([], 'application', 'files');

        /** @var File[] $files */
        $files = [];
        foreach ($entries as $entry) {
            $files[] = new File((bool) $entry['required'], $entry['path']);
        }

        return $files;
    }
}
