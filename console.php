#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Agnes\Commands\ReleaseCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

$path = dirname(__DIR__) . '/.env';
$dotenv = new Dotenv(false);
$dotenv->loadEnv($path);

$app = new Application();
$app->add(new ReleaseCommand());
$app->run();