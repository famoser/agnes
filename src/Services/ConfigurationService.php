<?php


namespace Agnes\Services;


use Symfony\Component\Yaml\Yaml;

class ConfigurationService
{
    /**
     * @param string $path
     * @return mixed
     * @throws \Exception
     */
    public function parseConfig(string $path)
    {

        $configFileContent = file_get_contents($path);
        $config = Yaml::parse($configFileContent);

        $this->replaceEnvVariables($config);

        return $config;
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
