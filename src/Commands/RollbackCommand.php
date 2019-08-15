<?php


namespace Agnes\Commands;

use Agnes\Actions\Rollback;
use Agnes\AgnesFactory;
use Agnes\Services\InstanceService;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends ConfigurationAwareCommand
{
    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * DeployCommand constructor.
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
        $this->setName('rollback')
            ->setDescription('Rollback a release to a previous version. 
            If target is supplied, it will only rollback instances which had that release active at some time.
            If source is supplied, it will only rollback instances with that release version active.
            If neither target nor source is supplied, it will rollback to the last release which was active.')
            ->setHelp('This command executes the rollback scripts & switches to the old release in specific environment(s).')
            ->addArgument("target", InputArgument::REQUIRED, "the instance(s) to rollback. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption("rollback-to", "rt", InputOption::VALUE_OPTIONAL, "name of the release to rollback to")
            ->addOption("rollback-from", "rs", InputOption::VALUE_OPTIONAL, "name of the release to rollback from");

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
        $target = $input->getArgument("target");
        $instances = $this->instanceService->getInstancesFromInstanceSpecification($target);

        $rollbackTo = $input->getOption("rollback-to");
        $rollbackFrom = $input->getOption("rollback-from");

        /** @var Rollback[] $rollbacks */
        $rollbacks = [];
        foreach ($instances as $instance) {
            $rollbackTarget = $instance->getRollbackTarget($rollbackTo, $rollbackFrom);
            if ($rollbackTarget !== null) {
                $rollbacks[] = new Rollback($instance, $rollbackTarget);
            }
        }

        $service = $this->getFactory()->createRollbackAction();
        $service->executeMultiple($rollbacks);
    }
}
