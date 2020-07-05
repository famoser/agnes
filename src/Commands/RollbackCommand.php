<?php

namespace Agnes\Commands;

use Agnes\Actions\Executor;
use Agnes\Actions\PayloadFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class RollbackCommand extends AgnesCommand
{
    public function configure()
    {
        $this->setName('rollback')
            ->setDescription('Rollback a release to a previous version')
            ->setHelp('This command executes the rollback scripts & switches to an older installation at the target instance(s).
If rollback-to is supplied, it will rollback instances to that an installation matching the version.
If rollback-from is supplied, it will rollback instances with that installation version.
If neither target nor source is supplied, it will rollback to the previously installed installation.')
            ->addArgument('target', InputArgument::REQUIRED, 'the instance(s) to rollback. '.AgnesCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption('rollback-to', null, InputOption::VALUE_OPTIONAL, 'name of the release or hash to rollback to')
            ->addOption('rollback-from', null, InputOption::VALUE_OPTIONAL, 'name of the release or hash to rollback from');

        parent::configure();
    }

    protected function enqueuePayloads(InputInterface $input, SymfonyStyle $io, PayloadFactory $payloadFactory, Executor $executor)
    {
        $target = $input->getArgument('target');
        $rollbackTo = $input->getOption('rollback-to');
        $rollbackFrom = $input->getOption('rollback-from');

        $rollbacks = $payloadFactory->createManyRollback($target, $rollbackTo, $rollbackFrom);
        foreach ($rollbacks as $rollback) {
            $executor->enqueue($rollback);
        }
    }
}
