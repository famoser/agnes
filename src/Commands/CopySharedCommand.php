<?php

namespace Agnes\Commands;

use Agnes\Services\TaskService;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopySharedCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('copy:shared')
            ->setDescription('Copies the shared data from the source to the target')
            ->setHelp('This copies the shared data from the source to the target to replicate environment(s).')
            ->addArgument('target', InputArgument::REQUIRED, 'the instances(s) to copy data to. '.AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addArgument('source', InputArgument::REQUIRED, 'the stage to copy from.');

        parent::configure();
    }

    /**
     * @throws Exception
     * @throws \Http\Client\Exception
     * @throws \Http\Client\Exception
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $target = $input->getArgument('target');
        $source = $input->getArgument('source');

        $taskService->addCopySharedTasks($target, $source);
    }
}
