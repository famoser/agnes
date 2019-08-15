<?php


namespace Agnes\Commands;

use Agnes\Actions\CopyShared;
use Agnes\AgnesFactory;
use Agnes\Models\Instance;
use Agnes\Services\InstanceService;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CopySharedCommand extends ConfigurationAwareCommand
{
    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * CopySharedCommand constructor.
     * @param AgnesFactory $factory
     * @param InstanceService $instanceService
     */
    public function __construct(AgnesFactory $factory, InstanceService $instanceService)
    {
        parent::__construct($factory);

        $this->instanceService = $instanceService;
    }


    public function configure()
    {
        $this->setName('copy:shared')
            ->setDescription('Copies the shared data from the source to the target.')
            ->setHelp('This copies the shared data from the source to the target to replicate environment(s).')
            ->addArgument("source", InputArgument::REQUIRED, "the instance(s) to copy data from. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addArgument("target", InputArgument::REQUIRED, "the instance(s) to replace the data from the source." . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument("source");
        $sourceInstances = $this->instanceService->getInstancesFromInstanceSpecification($source);

        $target = $input->getArgument("target");
        $targetInstances = $this->instanceService->getInstancesFromInstanceSpecification($target);

        $service = $this->getFactory()->createCopySharedAction();

        /** @var CopyShared[] $copyShareds */
        $copyShareds = [];
        foreach ($targetInstances as $targetInstance) {
            $matchingInstances = $targetInstance->getSameEnvironmentInstances($sourceInstances);
            if (count($matchingInstances) === 1) {
                $copyShareds[] = new CopyShared($matchingInstances[0], $targetInstance);
            }
        }

        $service->executeMultiple($copyShareds);
    }
}
