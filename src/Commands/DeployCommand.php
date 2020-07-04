<?php

namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\Actions\DeployAction;
use Agnes\AgnesFactory;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploy a release to a specific environment')
            ->setHelp('This command installs a release to a specific environment and if the installation succeeds, it publishes it.')
            ->addArgument('release or commitish', InputArgument::REQUIRED, 'name of the (github) release or commitish')
            ->addArgument('target', InputArgument::REQUIRED, 'the instance(s) to deploy to. '.AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION);

        parent::configure();
    }

    protected function getAction(AgnesFactory $factory): AbstractAction
    {
        return $factory->getDeployAction();
    }

    /**
     * @return AbstractPayload[]
     *
     * @throws Exception
     * @throws \Http\Client\Exception
     */
    protected function createPayloads(AbstractAction $action, InputInterface $input, OutputInterface $output): array
    {
        $releaseOrCommitish = $input->getArgument('release or commitish');
        $target = $input->getArgument('target');

        /* @var DeployAction $action */
        return $action->createMany($releaseOrCommitish, $target);
    }
}
