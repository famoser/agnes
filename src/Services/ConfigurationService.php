<?php


namespace Agnes\Services;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Connections\LocalConnection;
use Agnes\Models\Connections\SSHConnection;
use Agnes\Models\Policies\Policy;
use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Agnes\Models\Policies\StageWriteDownPolicy;
use Agnes\Models\Policies\StageWriteUpPolicy;
use Agnes\Models\Tasks\Filter;
use Agnes\Models\Tasks\Task;
use Agnes\Services\Configuration\Environment;
use Agnes\Services\Configuration\EditableFile;
use Agnes\Services\Configuration\GithubConfig;
use Agnes\Services\Configuration\Server;
use Symfony\Component\Yaml\Yaml;

class ConfigurationService
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var array
     */
    private $config = [];

    /**
     * ConfigurationService constructor.
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param string $path
     * @throws \Exception
     */
    public function loadConfig(string $path)
    {
        $configFileContent = file_get_contents($this->basePath . DIRECTORY_SEPARATOR . $path);
        $config = Yaml::parse($configFileContent);

        $this->replaceEnvVariables($config);

        $this->config = $config;
    }

    /**
     * @return GithubConfig
     * @throws \Exception
     */
    public function getGithubConfig()
    {
        $apiToken = $this->getConfigEntry("agnes", "github_api_token");
        $repository = $this->getConfigEntry("application", "repository");

        return new GithubConfig($apiToken, $repository);
    }

    /**
     * @return Connection
     * @throws \Exception
     */
    public function getBuildConnection()
    {
        $connection = $this->getConfigEntry("agnes", "build", "connection");

        return $this->getConnection($connection);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getBuildPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $this->getConfigEntry("agnes", "build", "path");
    }

    /**
     * @param string $task
     * @return string[]
     * @throws \Exception
     */
    public function getScripts(string $task)
    {
        return $this->getConfigEntryOrDefault([], "application", "scripts", $task);
    }

    /**
     * @param string[] ...$key
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     * @throws \Exception
     */
    private function getConfigEntry(...$key)
    {
        return $this->getValue($this->config, true, null, ...$key);
    }

    /**
     * @param $default
     * @param string[] ...$key
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     * @throws \Exception
     */
    private function getConfigEntryOrDefault($default, ...$key)
    {
        return $this->getValue($this->config, false, $default, ...$key);
    }

    /**
     * @param array $config
     * @param bool $throwOnMissing
     * @param $default
     * @param string $first
     * @param string[] ...$additionalDept
     * @return string|string[]|string[][]|string[][][]|string[][][][]
     * @throws \Exception
     */
    private function getValue(array $config, bool $throwOnMissing, $default, string $first, ...$additionalDept)
    {
        if (!isset($config[$first])) {
            if ($throwOnMissing) {
                throw new \Exception("key " . $first . " does not exist.");
            } else {
                return $default;
            }
        }

        $value = $config[$first];
        if (count($additionalDept) > 0) {
            return $this->getValue($value, ...$additionalDept);
        }

        return $value;
    }

    /**
     * @param array $config
     * @throws \Exception
     */
    private function replaceEnvVariables(array &$config)
    {
        foreach ($config as &$item) {
            if (is_array($item)) {
                $this->replaceEnvVariables($item);
            } else if (strpos($item, "%env(") === 0) {
                $envPart = substr($item, 5);
                if (substr_compare($envPart, ")%", -2) === 0) {
                    $envName = substr($envPart, 0, -2);
                    if (!isset($_ENV[$envName])) {
                        throw new \Exception("The requested environment value " . $envName . " is not set.");
                    }
                    $item = $_ENV[$envName];
                }
            }
        }
    }


    /**
     * @return Server[]
     * @throws \Exception
     */
    public function getServers(): array
    {
        $serverConfigs = $this->getConfigEntryOrDefault([], "servers");

        $servers = [];
        foreach ($serverConfigs as $serverName => $serverConfig) {
            $connection = $this->getConnection($serverConfig["connection"]);
            $path = $serverConfig["path"];
            $keepReleases = (int)$serverConfig["keep_releases"];

            $environments = [];
            foreach ($serverConfig["environments"] as $environmentName => $stages) {
                $environments[] = new Environment($environmentName, $stages);
            }

            $servers[] = new Server($serverName, $connection, $path, $keepReleases, $environments);
        }

        return $servers;
    }

    /**
     * @param string $type
     * @return Policy[]
     * @throws \Exception
     */
    public function getPolicies(string $type)
    {
        $policies = $this->getConfigEntryOrDefault([], "policies", $type);

        /** @var Policy[] $parsedPolicies */
        $parsedPolicies = [];
        foreach ($policies as $policy) {
            $filter = $this->getFilter($policy["filter"]);

            $policyType = $policy["type"];
            switch ($policyType) {
                case "stage_write_up":
                    $parsedPolicies[] = new StageWriteUpPolicy($filter, $policy["layers"]);
                    break;
                case "stage_write_down":
                    $parsedPolicies[] = new StageWriteDownPolicy($filter, $policy["layers"]);
                    break;
                case "release_whitelist":
                    $parsedPolicies[] = new ReleaseWhitelistPolicy($filter, $policy["commitishes"]);
                    break;
                default:
                    throw new \Exception("Unknown policy type: " . $policyType);
            }
        }

        return $parsedPolicies;
    }

    /**
     * @param string[] $filter
     * @return Filter
     */
    private function getFilter(array $filter)
    {
        $servers = isset($filter["servers"]) ? $filter["servers"] : [];
        $environments = isset($filter["environments"]) ? $filter["environments"] : [];
        $stages = isset($filter["stages"]) ? $filter["stages"] : [];

        return new Filter($servers, $environments, $stages);
    }

    /**
     * @param $connection
     * @return LocalConnection|SSHConnection
     * @throws \Exception
     */
    private function getConnection($connection)
    {
        $connectionType = $connection["type"];

        if ($connectionType === "local") {
            return new LocalConnection();
        } else if ($connectionType === "ssh") {
            $destination = $connection["destination"];
            return new SSHConnection($destination);
        } else {
            throw new \Exception("unknown connection type $connectionType");
        }
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    public function getSharedFolders()
    {
        return $this->getConfigEntryOrDefault([], "application", "shared_folders");
    }

    /**
     * @return EditableFile[]
     * @throws \Exception
     */
    public function getEditableFiles()
    {
        $files = $this->getConfigEntryOrDefault([], "application", "editable_files");

        /** @var EditableFile[] $editableFiles */
        $editableFiles = [];
        foreach ($files as $file) {
            $editableFiles[] = new EditableFile((bool)$file["required"], $file["path"]);
        }

        return $editableFiles;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
