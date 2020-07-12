<?php

namespace Agnes\Services\Task;

use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Services\InstanceService;
use Agnes\Services\Policy\CopySharedPolicyVisitor;
use Agnes\Services\Policy\DeployPolicyVisitor;
use Agnes\Services\Policy\NoPolicyVisitor;
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
     * PolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, InstanceService $instanceService)
    {
        $this->io = $io;
        $this->instanceService = $instanceService;
    }

    public function visitCopyShared(Copy $copyShared)
    {
        return new CopySharedPolicyVisitor($this->io, $copyShared);
    }

    public function visitDeploy(Deploy $deploy)
    {
        return new DeployPolicyVisitor($this->io, $this->instanceService, $deploy);
    }

    protected function visitDefault(AbstractTask $payload)
    {
        return new NoPolicyVisitor($this->io, $payload);
    }
}
