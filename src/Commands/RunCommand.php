<?php

namespace Agnes\Commands;

use Agnes\Services\TaskService;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('run')
            ->setDescription('Run a script on an instance')
            ->setHelp('This script with that name is run on the specified instance.')
            ->addArgument('target', InputArgument::REQUIRED, 'the instance(s) to run the script on. '.AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addArgument('script', InputArgument::REQUIRED, 'name of the script');

        parent::configure();
    }

    /**
     * @throws Exception
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $target = $input->getArgument('target');
        $script = $input->getArgument('script');

        $taskService->addRunTask($target, $script);
    }
}
