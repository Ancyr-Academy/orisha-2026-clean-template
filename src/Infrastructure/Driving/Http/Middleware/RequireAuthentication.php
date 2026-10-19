<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Middleware;

use Acme\Infrastructure\Driving\Http\Session\Session;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sends visitors to the login page, and exposes the logged-in account id as a request attribute.
 */
final readonly class RequireAuthentication implements MiddlewareInterface
{
    public const string ACCOUNT_ID = 'account_id';

    public function __construct(
        private Session $session,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $account = $this->session->get('account');

        if (!is_array($account) || !is_string($account['id'] ?? null)) {
            return $this->responseFactory->createResponse(303)->withHeader('Location', '/login');
        }

        return $handler->handle($request->withAttribute(self::ACCOUNT_ID, $account['id']));
    }
}
