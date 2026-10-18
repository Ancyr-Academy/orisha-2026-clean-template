<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http;

use Acme\Infrastructure\Driving\Http\Controller\HomeController;
use Acme\Infrastructure\Driving\Http\Controller\LoginController;
use Acme\Infrastructure\Driving\Http\Controller\LogoutController;
use Acme\Infrastructure\Driving\Http\Controller\RegisterController;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

final class HttpApplication
{
    public const string TEMPLATES_DIR = __DIR__ . '/templates';

    public static function create(ContainerInterface $container): App
    {
        $app = AppFactory::createFromContainer($container);

        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $logErrors = (bool) $container->get('app.log_errors');
        $app->addErrorMiddleware((bool) $container->get('app.debug'), $logErrors, $logErrors)
            ->getDefaultErrorHandler()
            ->forceContentType('text/html');

        $app->get('/', HomeController::class);
        $app->get('/register', RegisterController::class . ':show');
        $app->post('/register', RegisterController::class . ':submit');
        $app->get('/login', LoginController::class . ':show');
        $app->post('/login', LoginController::class . ':submit');
        $app->post('/logout', LogoutController::class);

        return $app;
    }
}
