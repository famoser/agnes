<?php

namespace Agnes\Commands;

use Agnes\AgnesFactory;
use Agnes\Services\ConfigurationService;
use Agnes\Services\TaskService;
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
     * @throws Exception
     */
    abstract protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService);

    /**
     * @return int|void|null
     *
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $input->getOption('config-file');
        $configFolder = $input->getOption('config-folder');
        $isDryRun = $input->getOption('dry-run');

        $io = new SymfonyStyle($input, $output);
        $factory = new AgnesFactory($io);

        // dry run note
        if ($isDryRun) {
            $io->note('dry run active; none of the commands will actually be executed.');
        }

        // load config
        $configurationService = $factory->getConfigurationService();
        if (!$this->loadConfigFile($io, $configurationService, $configFile) ||
            !$this->loadConfigFolder($io, $configurationService, $configFolder) ||
            !$factory->getConfigurationService()->validate()) {
            return 1;
        }

        // create payloads
        $io->section('creating tasks');
        $this->createTasks($input, $io, $factory->getTaskService());

        $tasks = $factory->getTaskService()->getTasks();
        if (0 === count($tasks)) {
            $io->note('nothing to execute');

            return 0;
        }

        $this->printPayloads($io, $tasks);

        if ($isDryRun) {
            $io->note('dry run active; finishing now.');

            return 0;
        }

        $io->section('executing tasks');
        $factory->getTaskService()->executeAll();

        $io->success('finished');

        return 0;
    }

    /**
     * @throws Exception
     */
    private function loadConfigFile(StyleInterface $style, ConfigurationService $configurationService, ?string $configFile): bool
    {
        // default config file
        if (null === $configFile) {
            $configFile = 'agnes.yml';
        }

        // read config file
        if (null !== $configFile) {
            $path = realpath($configFile);
            if (!is_file($path)) {
                $style->error('config file not found at '.$configFile);

                return false;
            }

            $configurationService->addConfig($path);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    private function loadConfigFolder(SymfonyStyle $io, ConfigurationService $configurationService, ?bool $configFolder): bool
    {
        if (null === $configFolder) {
            $configFolder = $configurationService->getAgnesConfigFolder();
        }

        // read config folder
        if (null !== $configFolder) {
            $path = realpath($configFolder);
            if (!is_dir($path)) {
                $io->error('config folder not found at '.$configFolder.' with working dir '.realpath(__DIR__));

                return false;
            }

            $configurationService->setConfigFolder($configFolder);

            $configFilePaths = glob($path.DIRECTORY_SEPARATOR.'*.yml');
            foreach ($configFilePaths as $configFilePath) {
                $configurationService->addConfig($configFilePath);
            }
        }

        return true;
    }

    private function printPayloads(SymfonyStyle $io, array $payloads): void
    {
        $io->text(count($payloads).' tasks created');
        $descriptions = [];
        foreach ($payloads as $payload) {
            $descriptions[] = $payload->describe();
        }
        $io->listing($descriptions);
    }
}
