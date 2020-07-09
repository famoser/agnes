<?php

namespace Agnes\Commands;

use Agnes\Models\Task\AbstractTask;
use Agnes\Services\TaskService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReleaseCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release')
            ->setHelp('This command compiles the specified commitish and then publishes it to github.')
            ->addArgument('release', InputArgument::REQUIRED, 'name of the release')
            ->addArgument('commitish', InputArgument::REQUIRED, 'branch or commit of the release');

        parent::configure();
    }

    /**
     * @return AbstractTask[]
     *
     * @throws \Exception
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $release = $input->getArgument('release');
        $commitish = $input->getArgument('commitish');

        $taskService->createRelease($commitish, $release);
    }
}
