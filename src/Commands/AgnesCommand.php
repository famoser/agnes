<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Commands;

use Agnes\AgnesFactory;
use Agnes\Models\Connection\LocalConnection;
use Agnes\Models\Executor\LinuxExecutor;
use Agnes\Services\ConfigurationService;
use Agnes\Services\TaskService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AgnesCommand extends Command
{
    public const INSTANCE_SPECIFICATION_EXPLANATION = '
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
        $this->addOption('config-path', null, InputOption::VALUE_OPTIONAL, 'agnes config folder path');
    }

    /**
     * @throws \Exception
     */
    abstract protected function createTasks(InputInterface $input, SymfonyStyle $io, TaskService $taskService);

    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $input->getOption('config-file');
        $configPath = $input->getOption('config-path');
        $isDryRun = $input->getOption('dry-run');

        $io = new SymfonyStyle($input, $output);
        $factory = new AgnesFactory($io);

        // dry run note
        if ($isDryRun) {
            $io->note('dry run active; none of the commands will actually be executed.');
        }

        // load config
        $configurationService = $factory->getConfigurationService();
        if (!$this->loadConfigFile($io, $configurationService, $configFile)
            || !$this->loadConfigFolder($io, $configurationService, $configPath)
            || !$factory->getConfigurationService()->validate()) {
            return 1;
        }

        // create payloads
        $io->title('creating tasks');
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

        $io->title('executing tasks');
        $factory->getTaskService()->executeAll();

        $io->success('finished');

        return 0;
    }

    /**
     * @throws \Exception
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
            if (!$path || !is_file($path)) {
                $style->error('config file not found at '.$configFile);

                return false;
            }

            $configurationService->addConfig($path);
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    private function loadConfigFolder(SymfonyStyle $io, ConfigurationService $configurationService, ?string $configPath): bool
    {
        if (null === $configPath) {
            $configPath = $configurationService->getConfigPath();
        }

        // if config path exists & same vlaue as configured, then pull the config repository
        if (null !== $configPath && $configPath === $configurationService->getConfigPath()) {
            $configConnection = new LocalConnection($io, new LinuxExecutor());
            $configRepository = $configurationService->getConfigRepositoryUrl();

            if (!is_dir($configPath)) {
                if (null === $configRepository) {
                    $io->error('config folder not found at '.$configPath.' with working dir '.getcwd().' and not config repository configured.');

                    return false;
                }

                $io->text('cloning config repository');
                $configConnection->checkoutRepository($configPath, $configRepository);
            } elseif (null !== $configRepository) {
                $io->text('pulling config repository');
                $configConnection->gitPull($configPath);
            }

            $configFolder = $configurationService->getConfigRepositoryFolder();
            $configPath = $configFolder ? $configPath.DIRECTORY_SEPARATOR.$configFolder : $configPath;
            $configurationService->setConfigFolder($configPath);

            $configFilePaths = glob($configPath.DIRECTORY_SEPARATOR.'*.yml');
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
