<?php

namespace Agnes\Services;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\PayloadFactory;
use Agnes\Services\Configuration\Action;
use Symfony\Component\Console\Style\StyleInterface;

class ActionService
{
    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @param AbstractPayload[] $payloads
     *
     * @return AbstractPayload[]
     */
    public function getPayloads(array $payloads): array
    {
        return $this->getPayloadsRecursively($payloads, 0);
    }

    private function getPayloadsRecursively(array $payloads, int $recursionDepth): array
    {
        if ($recursionDepth++ > 10) {
            $this->io->error('actions probably contain a loop; abort recursion at depth '.$recursionDepth);

            return [];
        }

        $followupPayloads = [];

        foreach ($payloads as $payload) {
            $actions = $this->configurationService->getActions($payload);
            foreach ($actions as $action) {
                $followupPayloads[] = $this->createPayload($action);
            }
        }

        $newFollowupPayloads = $this->getPayloadsRecursively($followupPayloads, $recursionDepth + 1);
        $followupPayloads = array_merge($followupPayloads, $newFollowupPayloads);

        return $followupPayloads;
    }

    private function createPayload(Action $action)
    {
        return null;
    }
}
