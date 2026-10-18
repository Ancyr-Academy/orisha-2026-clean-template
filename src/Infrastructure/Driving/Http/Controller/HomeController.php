<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Controller;

use Acme\Application\Bus\UseCaseBus;
use Acme\Application\UseCase\Home\HomeQuery;
use Acme\Application\UseCase\Home\HomeResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final readonly class HomeController
{
    public function __construct(
        private UseCaseBus $useCases,
        private Twig $twig,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var HomeResponse $result */
        $result = $this->useCases->execute(new HomeQuery());

        return $this->twig
            ->render($response, 'home.html.twig', ['message' => $result->message])
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
