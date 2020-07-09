<?php

namespace Agnes\Services;

use Agnes\Actions\PayloadFactory;
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
}
