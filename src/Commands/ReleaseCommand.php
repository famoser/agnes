<?php

namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\Actions\ReleaseAction;
use Agnes\AgnesFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseCommand extends AgnesCommand
{
    /**
     * ReleaseCommand constructor.
     */
    public function __construct(AgnesFactory $factory)
    {
        parent::__construct($factory);
    }

    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release')
            ->setHelp('This command compiles the specified commitish and then publishes it to github.')
            ->addArgument('release', InputArgument::REQUIRED, 'name of the release')
            ->addArgument('commitish', InputArgument::REQUIRED, 'branch or commit of the release');

        parent::configure();
    }

    protected function getAction(AgnesFactory $factory): AbstractAction
    {
        return $factory->createReleaseAction();
    }

    /**
     * @return AbstractPayload[]
     */
    protected function createPayloads(AbstractAction $action, InputInterface $input, OutputInterface $output): array
    {
        $name = $input->getArgument('release');
        $commitish = $input->getArgument('commitish');

        /** @var ReleaseAction $action */
        $release = $action->tryCreate($commitish, $name);

        return [$release];
    }
}
