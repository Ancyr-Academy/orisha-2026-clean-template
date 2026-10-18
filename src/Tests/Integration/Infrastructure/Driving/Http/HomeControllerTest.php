<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driving\Http;

final class HomeControllerTest extends HttpTestCase
{
    public function testGetHomeRendersHelloWorld(): void
    {
        $response = $this->request('GET', '/');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<title>Acme</title>', (string) $response->getBody());
        self::assertStringContainsString('<h1>Hello World</h1>', (string) $response->getBody());
    }

    public function testUnknownRouteReturnsNotFound(): void
    {
        $response = $this->request('GET', '/unknown');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringStartsWith('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testUnsupportedMethodReturnsMethodNotAllowed(): void
    {
        $response = $this->request('POST', '/');

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('GET', $response->getHeaderLine('Allow'));
    }
}
