<?php


namespace Agnes\Commands;

use Agnes\Services\ConfigurationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class ReleaseCommand extends Command
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     */
    public function __construct(ConfigurationService $configurationService)
    {
        parent::__construct();

        $this->configurationService = $configurationService;
    }

    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release.')
            ->setHelp('This command compiles & publishes a new release according to the passed configuration.')
            ->addOption('config', "c", InputOption::VALUE_OPTIONAL, "The config file.", "config.yml");
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configFilePath = $input->getOption("config");
        $config = $this->configurationService->parseConfig($configFilePath);


    }
}
