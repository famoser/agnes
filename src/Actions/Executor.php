<?php

namespace Agnes\Actions;

use Agnes\Actions\Visitors\ExecutionVisitor;
use Agnes\Actions\Visitors\ValidatorVisitor;

class Executor
{
    /**
     * @var AbstractPayload[]
     */
    private $queue = [];

    /**
     * @var ValidatorVisitor
     */
    private $validatorVisitor;

    /**
     * @var ExecutionVisitor
     */
    private $executionVisitor;

    public function enqueue(AbstractPayload $payload)
    {
        $this->queue[] = $payload;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        foreach ($this->queue as $item) {
            if ($item->accept($this->executionVisitor)) {
                $item->accept($this->validatorVisitor);
            }
        }
    }
}
