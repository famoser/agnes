<?php


namespace Agnes\Commands;

use Agnes\Models\Instance;
use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\Rollback\Rollback;
use Agnes\Services\RollbackService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends ConfigurationAwareCommand
{
    /**
     * @var RollbackService
     */
    private $rollbackService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * DeployCommand constructor.
     * @param ConfigurationService $configurationService
     * @param RollbackService $rollbackService
     * @param InstanceService $instanceService
     */
    public function __construct(ConfigurationService $configurationService, RollbackService $rollbackService, InstanceService $instanceService)
    {
        parent::__construct($configurationService);

        $this->rollbackService = $rollbackService;
        $this->instanceService = $instanceService;
    }

    public function configure()
    {
        $this->setName('rollback')
            ->setDescription('Rollback a release to a previous version. 
            If target is supplied, it will only rollback instances which had that release active at some time.
            If source is supplied, it will only rollback instances with that release version active.
            If neither target nor source is supplied, it will rollback to the last release which was active.')
            ->setHelp('This command executes the rollback scripts & switches to the old release in specific environment(s).')
            ->addArgument("target", InputArgument::REQUIRED, "the instance(s) to rollback. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption("rollback-to", "rt", InputOption::VALUE_OPTIONAL, "name of the release to rollback to")
            ->addOption("rollback-from", "rs", InputOption::VALUE_OPTIONAL, "name of the release to rollback from");

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument("target");
        $instances = $this->instanceService->getInstancesFromInstanceSpecification($target);
        $instances = $this->filterByCanRollbackToAny($instances);

        $rollbackTo = $input->getOption("rollback-to");
        if ($rollbackTo !== null) {
            $instances = $this->filterByCanRollbackTo($instances, $rollbackTo);
        }

        $rollbackFrom = $input->getOption("rollback-from");
        if ($rollbackFrom !== null) {
            $instances = $this->filterByCanRollbackFrom($instances, $rollbackFrom);
        }

        /** @var Rollback[] $rollbacks */
        $rollbacks = [];
        foreach ($instances as $instance) {
            if ($rollbackTo) {
                $installation = $instance->getInstallation($rollbackTo);
                $rollbacks[] = new Rollback($instance, $installation);
            } else {
                $installation = $instance->getPreviousInstallation();
                $rollbacks[] = new Rollback($instance, $installation);
            }
        }


        $this->rollbackService->rollbackMultiple($rollbacks);
    }

    /**
     * @param Instance[] $instances
     * @param string|null $releaseName
     * @return Instance[]
     */
    private function filterByCanRollbackTo(array $instances, ?string $releaseName)
    {
        /** @var Instance[] $result */
        $result = [];

        foreach ($instances as $instance) {
            if ($instance->isCurrentRelease($releaseName)) {
                continue;
            }

            $installation = $instance->getInstallation($releaseName);
            if ($installation !== null && $instance->getCurrentInstallation() !== null && $installation->getNumber() < $instance->getCurrentInstallation()->getNumber()) {
                $result[] = $installation;
            }
        }

        return $result;
    }

    /**
     * @param Instance[] $instances
     * @param string|null $releaseName
     *
     * @return Instance[]
     */
    private function filterByCanRollbackFrom(array $instances, ?string $releaseName)
    {
        /** @var Instance[] $result */
        $result = [];

        foreach ($instances as $instance) {
            if ($instance->isCurrentRelease($releaseName)) {
                $result[] = $instance;
            }
        }

        return $instances;
    }

    /**
     * @param Instance[] $instances
     * @return Instance[]
     */
    private function filterByCanRollbackToAny(array $instances)
    {
        /** @var Instance[] $result */
        $result = [];

        foreach ($instances as $instance) {
            if ($instance->getPreviousInstallation() !== null) {
                $result[] = $instance;
            }
        }

        return $instances;
    }
}
