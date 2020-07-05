<?php

namespace Agnes\Services;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\Visitors\ExecutionVisitor;
use Agnes\Actions\Visitors\ValidatorVisitor;
use Symfony\Component\Console\Style\StyleInterface;

class PayloadService
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var ExecutionVisitor
     */
    private $executionVisitor;

    /**
     * @var ValidatorVisitor
     */
    private $validatorVisitor;

    /**
     * PayloadService constructor.
     */
    public function __construct(ExecutionVisitor $executionVisitor, ValidatorVisitor $validatorVisitor)
    {
        $this->executionVisitor = $executionVisitor;
        $this->validatorVisitor = $validatorVisitor;
    }

    /**
     * @param AbstractPayload[] $payloads
     *
     * @throws \Exception
     */
    public function execute(array $payloads)
    {
        foreach ($payloads as $item) {
            if (!$item->accept($this->validatorVisitor)) {
                $this->io->text('skipping '.$item->describe().' ...');

                continue;
            }

            $this->io->text('executing '.$item->describe().' ...');
            $item->accept($this->executionVisitor);
        }
    }
}
