<?php

declare(strict_types=1);

use Acme\Infrastructure\Driving\Http\HttpApplication;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = (require dirname(__DIR__) . '/config/container.php')();

HttpApplication::create($container)->run();
