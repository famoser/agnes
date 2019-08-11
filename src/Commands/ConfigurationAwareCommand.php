<?php


namespace Agnes\Commands;

use Agnes\Services\ConfigurationService;
use Exception;
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
        $this->addOption('config', "c", InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "config file", ["agnes.yml"]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws Exception
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $configFilePaths = $input->getOption("config");
        foreach ($configFilePaths as $path) {
            foreach (glob($path) as $item) {
                $this->configurationService->addConfig($item);
            }
        }
    }
}
