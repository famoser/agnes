#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Agnes\CommandFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

$path = __DIR__ . '/.env';
$dotenv = new Dotenv(false);
$dotenv->loadEnv($path);

$app = new Application();

$commandFactory = new CommandFactory(__DIR__);
$app->addCommands($commandFactory->getCommands());

$app->run();
