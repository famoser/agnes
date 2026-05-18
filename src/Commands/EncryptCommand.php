<?php

namespace Agnes\Commands;

use Agnes\Services\TaskService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EncryptCommand extends AgnesCommand
{
    public function configure(): void
    {
        $this->setName('encrypt')
            ->setDescription('Encrypts all files that are marked for encryption')
            ->setHelp('For all data -> files which are marked with encrypted: true, this command creates an encrypted file {filename}.encrypted. It uses the encryption key defined by config:encryption_key.')
            ->addArgument('target', InputArgument::REQUIRED, 'the instances(s) for which to decrypt the files. ' . AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption('overwrite', InputArgument::OPTIONAL, 'whether to update all encrypted files, even if their content does not change.');

        parent::configure();
    }

    /**
     *
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService)
    {
        $target = $input->getArgument('target');
        $overwrite = (bool)$input->getOption('overwrite');

        $taskService->addEncryptTask($target, $overwrite);
    }
}
