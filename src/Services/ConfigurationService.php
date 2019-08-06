<?php


namespace Agnes\Services;


use Agnes\Services\Configuration\GithubConfig;
use Agnes\Services\Configuration\TaskConfig;
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
        $configFileContent = file_get_contents($this->basePath . $path);
        $config = Yaml::parse($configFileContent);

        $this->replaceEnvVariables($config);
    }

    /**
     * @return GithubConfig
     * @throws \Exception
     */
    public function getGithubConfig()
    {
        $apiToken = $this->getValue("agnes", "github_api_token");
        $repository = $this->getValue("application", "repository");

        return new GithubConfig($apiToken, $repository);
    }

    /**
     * @param string $task
     * @return TaskConfig
     * @throws \Exception
     */
    public function getTaskConfig(string $task)
    {
        $folder = $this->basePath . $this->getValue("agnes", "build_folder");
        $releaseScript = $this->getValue("application", "scripts", $task);

        return new TaskConfig($folder, $releaseScript);
    }

    /**
     * @param string $first
     * @param string[] ...$additionalDept
     * @return string|string[]
     * @throws \Exception
     */
    private function getValue(string $first, ...$additionalDept)
    {
        if (!isset($this->config[$first])) {
            throw new \Exception("key " . $first . " does not exist.");
        }

        if (count($additionalDept) > 0) {
            return $this->getValue(...$additionalDept);
        }

        return $this->config[$first];

    }

    /**
     * @param array $config
     * @throws \Exception
     */
    private function replaceEnvVariables(array $config)
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
