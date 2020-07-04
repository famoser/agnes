<?php

namespace Agnes\Services;

use Symfony\Component\Console\Style\StyleInterface;

class BuildService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * PublishService constructor.
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }
}
