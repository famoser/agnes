<?php

namespace Agnes\Commands;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\PayloadFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploy a release to a specific environment')
            ->setHelp('This command installs a release to a specific environment and if the installation succeeds, it publishes it.')
            ->addArgument('target', InputArgument::REQUIRED, 'the instance(s) to deploy to. '.AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addArgument('release or commitish', InputArgument::REQUIRED, 'name of the (github) release or commitish');

        parent::configure();
    }

    /**
     * @throws \Exception
     *
     * @return AbstractPayload[]
     */
    protected function createTasks(InputInterface $input, SymfonyStyle $io, PayloadFactory $payloadFactory): array
    {
        $releaseOrCommitish = $input->getArgument('release or commitish');
        $target = $input->getArgument('target');

        return $payloadFactory->createManyDeploy($releaseOrCommitish, $target);
    }
}
