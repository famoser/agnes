<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Policy;

use Agnes\Models\Task\AbstractTask;
use Agnes\Services\Task\ExecutionVisitor\BuildResult;
use Symfony\Component\Console\Style\StyleInterface;

class NeedsBuildResultPolicyVisitor extends NoPolicyVisitor
{
    /**
     * @var BuildResult|null
     */
    private $buildResult;

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var AbstractTask
     */
    private $task;

    /**
     * DeployPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, ?BuildResult $buildResult, AbstractTask $task)
    {
        parent::__construct($io, $task);

        $this->buildResult = $buildResult;
        $this->io = $io;
        $this->task = $task;
    }

    public function validate(): bool
    {
        if (null === $this->buildResult) {
            $this->io->error('To '.$this->task->describe().' a successful build it required.');

            return false;
        }

        return parent::validate();
    }
}
