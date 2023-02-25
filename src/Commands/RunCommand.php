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
     * @throws \Exception
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $target = $input->getArgument('target');
        $script = $input->getArgument('script');

        $taskService->addRunTask($target, $script);
    }
}
