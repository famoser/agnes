<?php


namespace Agnes\Commands;

use Agnes\Actions\Release;
use Agnes\Services\ConfigurationService;
use Agnes\Actions\ReleaseAction;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseCommand extends ConfigurationAwareCommand
{
    /**
     * @var ReleaseAction
     */
    private $releaseService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     * @param ReleaseAction $publishService
     */
    public function __construct(ConfigurationService $configurationService, ReleaseAction $publishService)
    {
        parent::__construct($configurationService);

        $this->releaseService = $publishService;
    }

    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release.')
            ->setHelp('This command compiles & publishes a new release according to the passed configuration.')
            ->addArgument("release", InputArgument::REQUIRED, "name of the release")
            ->addArgument("commitish", InputArgument::REQUIRED, "branch or commit of the release");

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
        $name = $input->getArgument("release");
        $commitish = $input->getArgument("commitish");
        $release = new Release($name, $commitish);

        $this->releaseService->publish($release);
    }
}
