<?php


namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\Actions\RollbackAction;
use Agnes\AgnesFactory;
use Agnes\Services\InstanceService;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends AgnesCommand
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
            ->setDescription('Rollback a release to a previous version')
            ->setHelp('This command executes the rollback scripts & switches to the old release in specific environment(s).
If target is supplied, it will only rollback instances which had that release active at some time.
If source is supplied, it will only rollback instances with that release version active.
If neither target nor source is supplied, it will rollback to the last release which was active')
            ->addArgument("target", InputArgument::REQUIRED, "the instance(s) to rollback. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption("rollback-to", null, InputOption::VALUE_OPTIONAL, "name of the release to rollback to")
            ->addOption("rollback-from", null, InputOption::VALUE_OPTIONAL, "name of the release to rollback from");

        parent::configure();
    }

    /**
     * @param AgnesFactory $factory
     * @return AbstractAction
     */
    protected function getAction(AgnesFactory $factory): AbstractAction
    {
        return $factory->createRollbackAction();
    }

    /**
     * @param AbstractAction $action
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return AbstractPayload[]
     * @throws Exception
     */
    protected function createPayloads(AbstractAction $action, InputInterface $input, OutputInterface $output): array
    {
        $target = $input->getArgument("target");
        $rollbackTo = $input->getOption("rollback-to");
        $rollbackFrom = $input->getOption("rollback-from");

        /** @var RollbackAction $action */
        return $action->createMany($target, $rollbackTo, $rollbackFrom);
    }
}
