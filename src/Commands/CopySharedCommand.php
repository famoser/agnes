<?php


namespace Agnes\Commands;

use Agnes\Models\Instance;
use Agnes\Services\ConfigurationService;
use Agnes\Services\CopyShared\CopyShared;
use Agnes\Services\CopySharedService;
use Agnes\Services\InstanceService;
use Agnes\Services\Rollback\Rollback;
use Agnes\Services\RollbackService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument("source", InputArgument::REQUIRED, "the instance(s) to copy data from. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addArgument("target", InputArgument::REQUIRED, "the instance(s) to replace the data from the source." . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION);

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
        $source = $input->getArgument("source");
        $sourceInstances = $this->instanceService->getInstancesFromInstanceSpecification($source);

        $target = $input->getArgument("target");
        $targetInstances = $this->instanceService->getInstancesFromInstanceSpecification($target);

        /** @var CopyShared[] $copyShareds */
        $copyShareds = [];
        foreach ($targetInstances as $targetInstance) {
            $source = $this->getMatch($sourceInstances, $targetInstance->getServerName(), $targetInstance->getEnvironmentName());
            if ($source !== null) {
                $copyShareds[] = new CopyShared($source, $targetInstance);
            }
        }

        $this->copySharedService->copySharedMultiple($copyShareds);
    }

    /**
     * @param Instance[] $instances
     * @param string $serverName
     * @param string $environmentName
     * @return Instance|null
     */
    private function getMatch(array $instances, string $serverName, string $environmentName)
    {
        foreach ($instances as $instance) {
            if ($instance->getServerName() === $serverName && $instance->getEnvironmentName() === $environmentName) {
                return $instance;
            }
        }

        return null;
    }
}
