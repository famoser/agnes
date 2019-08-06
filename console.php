#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Agnes\Commands\ReleaseCommand;
use Agnes\Release\CompressionService;
use Agnes\Release\GithubService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\TaskExecutionService;
use Http\Adapter\Guzzle6\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

$path = __DIR__ . '/.env';
$dotenv = new Dotenv(false);
$dotenv->loadEnv($path);

$configService = new ConfigurationService(__DIR__);
$client = Client::createWithConfig([]);
$releaseService = new GithubService($client);
$taskExecutionService = new TaskExecutionService();
$compressionService = new CompressionService();

$app = new Application();
$app->add(new ReleaseCommand($configService, $releaseService, $taskExecutionService, $compressionService));
$app->run();
