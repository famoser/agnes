<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Commands;

use Agnes\Services\TaskService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReleaseCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('release')
            ->setDescription('Publish a new release to github')
            ->setHelp('This command compiles the specified commitish and then publishes it to github.')
            ->addArgument('release', InputArgument::REQUIRED, 'name of the release')
            ->addArgument('commitish', InputArgument::REQUIRED, 'branch or commit of the release');

        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $release = $input->getArgument('release');
        $commitish = $input->getArgument('commitish');

        $taskService->addReleaseTask($commitish, $release);
    }
}
