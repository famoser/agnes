<?php


namespace Agnes\Commands;

use Agnes\Services\ConfigurationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ConfigurationAwareCommand extends Command
{
    /**
     * @var ConfigurationService
     */
    protected $configurationService;

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
        $this->addOption('config', "c", InputOption::VALUE_OPTIONAL, "config file", "config.yml");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $configFilePath = $input->getOption("config");
        $this->configurationService->loadConfig($configFilePath);

        parent::initialize($input, $output);
    }
}
