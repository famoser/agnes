#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Agnes\Commands\ReleaseCommand;
use Agnes\Services\ConfigurationService;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

$path = __DIR__ . '/.env';
$dotenv = new Dotenv(false);
$dotenv->loadEnv($path);

$configService = new ConfigurationService();

$app = new Application();
$app->add(new ReleaseCommand($configService));
$app->run();
