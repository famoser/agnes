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

class ClearCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('clear')
            ->setDescription('Clears failed installations from a specific environment')
            ->setHelp('This command removes any folder that does not contain a valid installation (=was never online).')
            ->addArgument('target', InputArgument::REQUIRED, 'the instance(s) to clear. '.AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION);

        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $target = $input->getArgument('target');

        $taskService->addClearTask($target);
    }
}
