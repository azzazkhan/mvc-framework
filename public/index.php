<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Asset\Vite;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application(dirname(__DIR__));

$app->run();
