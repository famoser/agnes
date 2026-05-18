<?php

namespace Agnes\Commands;

use Agnes\Services\TaskService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DecryptCommand extends AgnesCommand
{
    public function configure(): void
    {
        $this->setName('decrypt')
            ->setDescription('Decrypts all files that are marked for encryption')
            ->setHelp('For all data -> files which are marked with encrypted: true, this command looks for {filename}.encrypted, and decrypts it by writing to {filename}. It uses the encryption key defined by config:encryption_key.')
            ->addArgument('target', InputArgument::REQUIRED, 'the instances(s) for which to decrypt the files. ' . AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption('check', InputArgument::OPTIONAL, 'whether to only check that the encrypted and decrypted files are equal.');

        parent::configure();
    }

    /**
     *
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService): void
    {
        $target = $input->getArgument('target');
        $check = (bool)$input->getOption('check');

        $taskService->addDecryptTask($target, $check);
    }
}
