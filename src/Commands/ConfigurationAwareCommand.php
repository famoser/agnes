<?php


namespace Agnes\Commands;

use Agnes\AgnesFactory;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ConfigurationAwareCommand extends Command
{
    /**
     * @var AgnesFactory
     */
    private $factory;

    /**
     * ReleaseCommand constructor.
     * @param AgnesFactory $factory
     */
    public function __construct(AgnesFactory $factory)
    {
        // TODO: set config over factory
        parent::__construct();

        $this->factory = $factory;
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
            $files = glob($path);
            if (count($files) === 0) {
                throw new Exception("no config files found at path " . realpath(__DIR__) . " for $path");
            }

            foreach ($files as $item) {
                $this->factory->addConfig($item);
            }
        }
    }

    /**
     * @return AgnesFactory
     */
    protected function getFactory(): AgnesFactory
    {
        return $this->factory;
    }
}
