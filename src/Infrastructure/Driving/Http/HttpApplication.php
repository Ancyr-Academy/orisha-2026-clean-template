<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http;

use Acme\Infrastructure\Driving\Http\Controller\HomeController;
use Acme\Infrastructure\Driving\Http\Controller\LoginController;
use Acme\Infrastructure\Driving\Http\Controller\LogoutController;
use Acme\Infrastructure\Driving\Http\Controller\PatientController;
use Acme\Infrastructure\Driving\Http\Controller\RegisterController;
use Acme\Infrastructure\Driving\Http\Middleware\RequireAuthentication;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Middleware\MethodOverrideMiddleware;

final class HttpApplication
{
    public const string TEMPLATES_DIR = __DIR__ . '/templates';

    public static function create(ContainerInterface $container): App
    {
        $app = AppFactory::createFromContainer($container);

        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        // HTML forms only send GET and POST: a hidden "_METHOD" field lets them reach PUT and DELETE routes
        $app->add(new MethodOverrideMiddleware());
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

        $app->group('/patients', function (RouteCollectorProxyInterface $patients): void {
            $patients->get('', PatientController::class . ':index');
            $patients->get('/new', PatientController::class . ':new');
            $patients->post('', PatientController::class . ':create');
            $patients->get('/{id}/edit', PatientController::class . ':edit');
            $patients->put('/{id}', PatientController::class . ':update');
            $patients->delete('/{id}', PatientController::class . ':delete');
        })->add(RequireAuthentication::class);

        return $app;
    }
}
