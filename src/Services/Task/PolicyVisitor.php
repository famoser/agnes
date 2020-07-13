<?php

namespace Agnes\Services\Task;

use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Services\InstanceService;
use Agnes\Services\Policy\CopyPolicyVisitor;
use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\NoPolicyVisitor;
use Agnes\Services\Task\ExecutionVisitor\BuildResult;
use Symfony\Component\Console\Style\StyleInterface;

class PolicyVisitor extends AbstractTaskVisitor
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var BuildResult
     */
    private $buildResult;

    /**
     * PolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, InstanceService $instanceService, ?BuildResult $buildResult)
    {
        $this->io = $io;
        $this->instanceService = $instanceService;
        $this->buildResult = $buildResult;
    }

    public function visitCopy(Copy $copy)
    {
        return new CopyPolicyVisitor($this->io, $copy);
    }

    public function visitDeploy(Deploy $deploy)
    {
        // $this->buildResult is never null here
        // a deploy task is only ever executed (hence policy checked for) after a build task has finished
        // hence while this might look like a null-ref, application-wise this is fine
        return new DeployPolicyVisitor($this->io, $this->instanceService, $deploy, $this->buildResult);
    }

    protected function visitDefault(AbstractTask $payload)
    {
        return new NoPolicyVisitor($this->io, $payload);
    }
}
