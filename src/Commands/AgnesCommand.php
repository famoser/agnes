<?php

namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\AgnesFactory;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AgnesCommand extends Command
{
    const INSTANCE_SPECIFICATION_EXPLANATION = '
            Instances are specified in the form server:environment:stage (like aws:example.com:production deploys to production of example.com on the aws server). 
            Replace entries with stars to not enforce a constraint (like *:*:production would deploy to all production stages).
            Separate entries with comma (,) to enforce an OR constraint (like *:*:staging,production would deploy to all staging & production instances).';

    /**
     * add options for config file & additional config folder.
     */
    public function configure()
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'should the command skip the actual execution (useful for you to preview the potential impact)');
        $this->addOption('config-file', null, InputOption::VALUE_OPTIONAL, 'agnes main config file');
        $this->addOption('config-folder', null, InputOption::VALUE_OPTIONAL, 'agnes config folder');
    }

    /**
     * @return int|void|null
     *
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $factory = new AgnesFactory($io);

        if (!$this->tryLoadConfig($factory, $input, $io)) {
            return 1;
        }

        $isDryRun = $input->getOption('dry-run');
        if ($isDryRun) {
            $io->note('dry run active; none of the commands will actually be executed.');
        }

        $action = $this->getAction($factory);

        $io->section('creating payloads');
        $payloads = $this->createPayloads($action, $input, $output);
        if (0 === count($payloads)) {
            $io->caution('nothing to execute');

            return 0;
        } else {
            $io->text(count($payloads).' tasks created');
            $descriptions = [];
            foreach ($payloads as $payload) {
                $descriptions[] = $payload->describe();
            }
            $io->listing($descriptions);
        }

        $io->section('executing payloads');
        foreach ($payloads as $payload) {
            $io->text($payload->describe());

            if (!$action->canExecute($payload, $output)) {
                $io->warning('execution of "'.$payload->describe().'" blocked by policy; skipping');
            } elseif (!$isDryRun) {
                $action->execute($payload, $output);
                $io->success('done');
            }
        }

        $io->success('finished');

        return 0;
    }

    /**
     * @throws Exception
     */
    protected function tryLoadConfig(AgnesFactory $factory, InputInterface $input, StyleInterface $io): bool
    {
        $configFilePath = $input->getOption('config-file');
        $configFolder = $input->getOption('config-folder');

        // default config file
        if (null === $configFilePath) {
            if (file_exists('agnes.yml')) {
                $configFilePath = 'agnes.yml';
                $io->text('found agnes.yml in project root and will use it');
            } elseif (null === $configFolder) {
                $io->error('no configuration found or supplied');

                return false;
            }
        }

        // read config file
        if (null !== $configFilePath) {
            $path = realpath($configFilePath);
            if (!is_file($path)) {
                $io->error('config file not found at '.$configFilePath);

                return false;
            }

            $factory->addConfig($path);
        }

        if (null === $configFolder) {
            $configFolder = $factory->getConfigurationService()->getAgnesConfigFolder();
        }

        // read config folder
        if (null !== $configFolder) {
            $path = realpath($configFolder);
            if (!is_dir($path)) {
                $io->error('config folder not found at '.$configFolder.' with working dir '.realpath(__DIR__));

                return false;
            }

            $factory->getConfigurationService()->setConfigFolder($configFolder);

            $configFilePaths = glob($path.DIRECTORY_SEPARATOR.'*.yml');
            foreach ($configFilePaths as $configFilePath) {
                $factory->addConfig($configFilePath);
            }
        }

        $agnesVersion = 3;
        if ($factory->getConfigurationService()->getAgnesVersion() !== $agnesVersion) {
            $io->error('expected '.$agnesVersion.' as the agnes.version value');

            return false;
        }

        return true;
    }

    abstract protected function getAction(AgnesFactory $factory): AbstractAction;

    /**
     * @return AbstractPayload[]
     */
    abstract protected function createPayloads(AbstractAction $action, InputInterface $input, OutputInterface $output): array;
}
