<?php

namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\Actions\CopySharedAction;
use Agnes\AgnesFactory;
use Agnes\Services\InstanceService;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CopySharedCommand extends AgnesCommand
{
    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * CopySharedCommand constructor.
     */
    public function __construct(AgnesFactory $factory, InstanceService $instanceService)
    {
        parent::__construct($factory);

        $this->instanceService = $instanceService;
    }

    public function configure()
    {
        $this->setName('copy:shared')
            ->setDescription('Copies the shared data from the source to the target')
            ->setHelp('This copies the shared data from the source to the target to replicate environment(s).')
            ->addArgument('target', InputArgument::REQUIRED, 'the instances(s) to copy data to. '.AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addArgument('source', InputArgument::REQUIRED, 'the stage to copy from.');

        parent::configure();
    }

    protected function getAction(AgnesFactory $factory): AbstractAction
    {
        return $factory->createCopySharedAction();
    }

    /**
     * @return AbstractPayload[]
     *
     * @throws Exception
     */
    protected function createPayloads(AbstractAction $action, InputInterface $input, OutputInterface $output): array
    {
        $target = $input->getArgument('target');
        $source = $input->getArgument('source');

        /* @var CopySharedAction $action */
        return $action->createMany($target, $source, $output);
    }
}
