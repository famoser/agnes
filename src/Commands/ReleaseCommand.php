<?php


namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\Actions\ReleaseAction;
use Agnes\AgnesFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class ReleaseCommand extends AgnesCommand
{
    /**
     * ReleaseCommand constructor.
     * @param AgnesFactory $factory
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
            ->addArgument("release", InputArgument::REQUIRED, "name of the release")
            ->addArgument("commitish", InputArgument::REQUIRED, "branch or commit of the release");

        parent::configure();
    }

    /**
     * @param AgnesFactory $factory
     * @return AbstractAction
     */
    protected function getAction(AgnesFactory $factory): AbstractAction
    {
        return $factory->createReleaseAction();
    }

    /**
     * @param AbstractAction $action
     * @param InputInterface $input
     * @return AbstractPayload[]
     */
    protected function createPayloads(AbstractAction $action, InputInterface $input): array
    {
        $name = $input->getArgument("release");
        $commitish = $input->getArgument("commitish");

        /** @var ReleaseAction $action */
        $release = $action->tryCreate($name, $commitish);

        if ($release == null) {
            return [];
        }

        return [$release];
    }
}
