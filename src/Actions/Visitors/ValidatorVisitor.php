<?php

namespace Agnes\Actions\Visitors;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\CopyShared;
use Symfony\Component\Console\Style\StyleInterface;

class ValidatorVisitor extends AbstractActionVisitor
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * ValidatorVisitor constructor.
     */
    public function __construct(StyleInterface $io)
    {
        $this->io = $io;
    }

    public function visitCopyShared(CopyShared $copyShared): bool
    {
        // does not make sense to copy from itself
        if ($copyShared->getSource()->equals($copyShared->getTarget())) {
            $this->io->warning('Cannot execute '.$copyShared->describe().': copy shared to itself does not make sense.');

            return false;
        }

        return true;
    }

    public function visitDefault(AbstractPayload $payload): bool
    {
        return true;
    }
}
