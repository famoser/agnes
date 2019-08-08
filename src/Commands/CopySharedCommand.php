<?php


namespace Agnes\Commands;

use Agnes\Models\Tasks\Instance;
use Agnes\Services\ConfigurationService;
use Agnes\Services\CopySharedService;
use Agnes\Services\InstanceService;
use Agnes\Services\Rollback\Rollback;
use Agnes\Services\RollbackService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CopySharedCommand extends ConfigurationAwareCommand
{
    /**
     * @var CopySharedService
     */
    private $copySharedService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * CopySharedCommand constructor.
     * @param ConfigurationService $configurationService
     * @param CopySharedService $copySharedService
     * @param InstanceService $instanceService
     */
    public function __construct(ConfigurationService $configurationService, CopySharedService $copySharedService, InstanceService $instanceService)
    {
        parent::__construct($configurationService);

        $this->copySharedService = $copySharedService;
        $this->instanceService = $instanceService;
    }


    public function configure()
    {
        $this->setName('copy:shared')
            ->setDescription('Copies the shared data from the source to the target.')
            ->setHelp('This copies the shared data from the source to the target to replicate environment(s).')
            ->addOption("source", "s", InputOption::VALUE_REQUIRED, "the instance(s) to copy data from. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption("target", "t", InputOption::VALUE_REQUIRED, "the instance(s) to replace the data from the source." . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getOption("source");
        $sourceInstances = $this->instanceService->getInstancesFromInstanceSpecification($source);

        $target = $input->getOption("target");
        $targetInstances = $this->instanceService->getInstancesFromInstanceSpecification($target);

        /**
         * for each target, find matching source (same environment, single result)
         * then copy
         */

        $this->copySharedService->copySharedMultiple([]);
    }
}
