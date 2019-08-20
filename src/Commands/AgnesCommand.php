<?php


namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\AgnesFactory;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AgnesCommand extends Command
{
    /**
     * @var AgnesFactory
     */
    private $factory;

    /**
     * ReleaseCommand constructor.
     * @param AgnesFactory $factory
     */
    public function __construct(AgnesFactory $factory)
    {
        parent::__construct();

        $this->factory = $factory;
    }

    /**
     * add options for config file & additional config folder
     */
    public function configure()
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, "should the command skip the actual execution (useful for you to preview the potential impact)");
        $this->addOption('config-file', null, InputOption::VALUE_OPTIONAL, "agnes main config file");
        $this->addOption('config-folder', null, InputOption::VALUE_OPTIONAL, "agnes config folder");
    }

    /**
     * @var string|null
     */
    private $agnesConfigFolder;

    /**
     * @return string|null
     */
    protected function getConfigFolder(): ?string
    {
        return $this->agnesConfigFolder;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->tryLoadConfig($input, $output)) {
            return 1;
        }

        $isDryRun = $input->getOption('dry-run');
        if ($isDryRun) {
            $output->writeln("dry run active; none of the commands will be actually executed.");
        }

        $action = $this->getAction($this->factory);

        $output->writeln("");
        $payloads = $this->createPayloads($action, $input);
        if (count($payloads) === 0) {
            $output->writeln("nothing to execute");

            return 0;
        } else {
            $output->writeln(count($payloads) . " tasks created:");
            foreach ($payloads as $payload) {
                $output->writeln($payload->describe());
            }

            $output->writeln("");
        }

        foreach ($payloads as $payload) {
            $description = $payload->describe();

            if (!$action->canExecute($payload)) {
                $output->writeln("execution of " . $description . " blocked by policy; skipping");
                $output->writeln("");
            } else if (!$isDryRun) {
                $output->writeln($description);
                $action->execute($payload, $output);
                $output->writeln("executing finished");
                $output->writeln("");
            }
        }


        $output->writeln("command execution finished");

        return 0;
    }

    /**
     * @param AgnesFactory $factory
     * @return AbstractAction
     */
    abstract protected function getAction(AgnesFactory $factory): AbstractAction;

    /**
     * @param AbstractAction $action
     * @param InputInterface $input
     * @return AbstractPayload[]
     */
    abstract protected function createPayloads(AbstractAction $action, InputInterface $input): array;

    /**
     * @return AgnesFactory
     */
    protected function getFactory(): AgnesFactory
    {
        return $this->factory;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws Exception
     */
    protected function tryLoadConfig(InputInterface $input, OutputInterface $output): bool
    {
        $configFilePath = $input->getOption("config-file");
        $configFolder = $input->getOption("config-folder");

        // default config file
        if ($configFilePath === null && $configFolder === null) {
            $output->writeln("using default config file agnes.yml because no config option was supplied");
            $configFilePath = "agnes.yml";
        }

        // read config file
        if ($configFilePath !== null) {
            $path = realpath($configFilePath);
            if (!is_file($path)) {
                $output->writeln("config file not found at " . $configFilePath);

                return false;
            }

            $this->factory->addConfig($path);
        }

        // read config folder
        if ($configFolder !== null) {
            $path = realpath($configFolder);
            if (!is_dir($path)) {
                $output->writeln("config folder not found at " . $configFilePath);

                return false;
            }

            $this->agnesConfigFolder = $path;

            $configFilePaths = glob($path . DIRECTORY_SEPARATOR . "*.yml");
            foreach ($configFilePaths as $configFilePath) {
                $this->factory->addConfig($configFilePath);
            }
        }

        return true;
    }
}
