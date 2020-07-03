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

abstract class AgnesCommand extends Command
{
    const INSTANCE_SPECIFICATION_EXPLANATION = '
            Instances are specified in the form server:environment:stage (like aws:example.com:production deploys to production of example.com on the aws server). 
            Replace entries with stars to not enforce a constraint (like *:*:production would deploy to all production stages).
            Separate entries with comma (,) to enforce an OR constraint (like *:*:staging,production would deploy to all staging & production instances).';

    /**
     * @var AgnesFactory
     */
    private $factory;

    /**
     * ReleaseCommand constructor.
     */
    public function __construct(AgnesFactory $factory)
    {
        parent::__construct();

        $this->factory = $factory;
    }

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
        if (!$this->tryLoadConfig($input, $output)) {
            return 1;
        }

        $isDryRun = $input->getOption('dry-run');
        if ($isDryRun) {
            $output->writeln('dry run active; none of the commands will be actually executed.');
        }

        $action = $this->getAction($this->factory);

        $output->writeln('');
        $payloads = $this->createPayloads($action, $input, $output);
        if (0 === count($payloads)) {
            $output->writeln('nothing to execute');

            return 0;
        } else {
            $output->writeln(count($payloads).' tasks created:');
            foreach ($payloads as $payload) {
                $output->writeln($payload->describe());
            }

            $output->writeln('');
        }

        $output->writeln('starting execution...');
        $output->writeln('======================');
        $output->writeln('');

        foreach ($payloads as $payload) {
            $description = $payload->describe();

            if (!$action->canExecute($payload, $output)) {
                $output->writeln('execution of "'.$description.'" blocked by policy; skipping');
                $output->writeln('');
            } elseif (!$isDryRun) {
                $output->writeln($description);
                $action->execute($payload, $output);
                $output->writeln('execution finished');
                $output->writeln('');
            }
        }
        $output->writeln('======================');
        $output->writeln('all tasks completed.');

        return 0;
    }

    /**
     * @throws Exception
     */
    protected function tryLoadConfig(InputInterface $input, OutputInterface $output): bool
    {
        $configFilePath = $input->getOption('config-file');
        $configFolder = $input->getOption('config-folder');

        // default config file
        if (null === $configFilePath) {
            if (file_exists('agnes.yml')) {
                $configFilePath = 'agnes.yml';
                $output->writeln('found agnes.yml in project root and will use it');
            } elseif (null === $configFolder) {
                $output->writeln('no configuration found or supplied');

                return false;
            }
        }

        // read config file
        if (null !== $configFilePath) {
            $path = realpath($configFilePath);
            if (!is_file($path)) {
                $output->writeln('config file not found at '.$configFilePath);

                return false;
            }

            $this->factory->addConfig($path);
        }

        if (null === $configFolder) {
            $configFolder = $this->factory->getConfigurationService()->getAgnesConfigFolder();
        }

        // read config folder
        if (null !== $configFolder) {
            $path = realpath($configFolder);
            if (!is_dir($path)) {
                $output->writeln('config folder not found at '.$configFolder.' with working dir '.realpath(__DIR__));

                return false;
            }

            $this->factory->getConfigurationService()->setConfigFolder($configFolder);

            $configFilePaths = glob($path.DIRECTORY_SEPARATOR.'*.yml');
            foreach ($configFilePaths as $configFilePath) {
                $this->factory->addConfig($configFilePath);
            }
        }

        $agnesVersion = 3;
        if ($this->factory->getConfigurationService()->getAgnesVersion() !== $agnesVersion) {
            $output->writeln('expected '.$agnesVersion.' as the agnes.version value');

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
