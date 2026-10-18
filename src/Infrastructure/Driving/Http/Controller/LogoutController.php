<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Controller;

use Acme\Infrastructure\Driving\Http\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LogoutController
{
    public function __construct(
        private Session $session,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->session->destroy();

        return $response->withStatus(303)->withHeader('Location', '/');
    }
}
