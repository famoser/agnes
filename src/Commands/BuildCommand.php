<?php

namespace Agnes\Commands;

use Agnes\Services\TaskService;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('build')
            ->setDescription('Build the commitish.')
            ->setHelp('This command compiles the specified commitish (useful for testing).')
            ->addArgument('commitish', InputArgument::REQUIRED, 'branch or commit of the release');

        parent::configure();
    }

    /**
     * @throws Exception
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $commitish = $input->getArgument('commitish');

        $taskService->addBuildTask($commitish);
    }
}
