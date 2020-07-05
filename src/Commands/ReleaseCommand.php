<?php

namespace Agnes\Commands;

use Agnes\Actions\Executor;
use Agnes\Actions\PayloadFactory;
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

    protected function enqueuePayloads(InputInterface $input, SymfonyStyle $io, PayloadFactory $payloadFactory, Executor $executor)
    {
        $release = $input->getArgument('release');
        $commitish = $input->getArgument('commitish');

        $payload = $payloadFactory->createRelease($commitish, $release);
        $executor->enqueue($payload);
    }
}
