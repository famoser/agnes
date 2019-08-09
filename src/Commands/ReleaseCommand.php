<?php


namespace Agnes\Commands;

use Agnes\Services\Release\Release;
use Agnes\Services\ConfigurationService;
use Agnes\Services\ReleaseService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseCommand extends ConfigurationAwareCommand
{
    /**
     * @var ReleaseService
     */
    private $publishService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     * @param ReleaseService $publishService
     */
    public function __construct(ConfigurationService $configurationService, ReleaseService $publishService)
    {
        parent::__construct($configurationService);

        $this->publishService = $publishService;
    }

    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release.')
            ->setHelp('This command compiles & publishes a new release according to the passed configuration.')
            ->addOption("release", "r", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("commitish", "b", InputOption::VALUE_REQUIRED, "branch or commit of the release");

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getOption("release");
        $commitish = $input->getOption("commitish");
        $release = new Release($name, $commitish);

        $this->publishService->publish($release);
    }
}
