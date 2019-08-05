<?php


namespace Agnes\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class ReleaseCommand extends Command
{
    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release.')
            ->setHelp('This command compiles & publishes a new release according to the passed configuration.')
            ->addOption('config', "c", InputOption::VALUE_OPTIONAL, "The config file.");
    }

    private function parseConfig(string $path)
    {

        $configFileContent = file_get_contents($path);
        return Yaml::parse($configFileContent);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configFilePath = $input->getOption("config");
        $config = $this->parseConfig($configFilePath);
    }
}