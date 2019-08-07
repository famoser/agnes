<?php


namespace Agnes\Services;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Tasks\Task;
use Agnes\Models\Connections\LocalConnection;
use Agnes\Models\Connections\SSHConnection;
use Agnes\Services\Configuration\GithubConfig;
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
        $connectionType = $connection["type"];

        if ($connectionType === "local") {
            $path = $this->basePath . DIRECTORY_SEPARATOR . $connection["path"];
            return new LocalConnection($path);
        } else if ($connectionType === "ssh") {
            $destination = $connection["destination"];
            return new SSHConnection($connection["path"], $destination);
        } else {
            throw new \Exception("unknown connection type $connectionType");
        }
    }

    /**
     * @param string $task
     * @return Task
     * @throws \Exception
     */
    public function getTaskConfig(string $task)
    {
        return new Task($this->getConfigEntry("application", "scripts", $task));
    }

    /**
     * @param string[] ...$key
     * @return string|string[]
     * @throws \Exception
     */
    private function getConfigEntry(...$key)
    {
        return $this->getValue($this->config, ...$key);
    }

    /**
     * @param array $config
     * @param string $first
     * @param string[] ...$additionalDept
     * @return string|string[]
     * @throws \Exception
     */
    private function getValue(array $config, string $first, ...$additionalDept)
    {
        if (!isset($config[$first])) {
            throw new \Exception("key " . $first . " does not exist.");
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
}
