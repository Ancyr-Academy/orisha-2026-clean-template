<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driving\Http;

final class AuthenticationTest extends HttpTestCase
{
    public function testItShowsTheRegistrationForm(): void
    {
        $response = $this->request('GET', '/register');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<form method="post" action="/register">', (string) $response->getBody());
    }

    public function testRegisteringLogsTheVisitorIn(): void
    {
        $response = $this->request('POST', '/register', ['email' => 'john@acme.test', 'password' => 'correct horse']);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/', $response->getHeaderLine('Location'));
        self::assertSame(1, $this->transactions->commits);
        self::assertSame(1, $this->session->regenerations);
        self::assertStringContainsString('Logged in as john@acme.test', (string) $this->request('GET', '/')->getBody());
    }

    public function testRegistrationErrorsAreShownOnTheForm(): void
    {
        $response = $this->request('POST', '/register', ['email' => '<b>john</b>', 'password' => 'correct horse']);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(1, $this->transactions->rollbacks);
        self::assertStringContainsString('Please provide a valid email address.', (string) $response->getBody());
        self::assertStringContainsString('value="&lt;b&gt;john&lt;/b&gt;"', (string) $response->getBody());
        self::assertNull($this->session->get('account'));
    }

    public function testLoggingInWithValidCredentials(): void
    {
        $this->request('POST', '/register', ['email' => 'john@acme.test', 'password' => 'correct horse']);
        $this->request('POST', '/logout');

        $response = $this->request('POST', '/login', ['email' => 'john@acme.test', 'password' => 'correct horse']);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/', $response->getHeaderLine('Location'));
        self::assertSame('john@acme.test', $this->session->get('account')['email']);
    }

    public function testLoggingInWithInvalidCredentials(): void
    {
        $this->request('POST', '/register', ['email' => 'john@acme.test', 'password' => 'correct horse']);
        $this->request('POST', '/logout');

        $response = $this->request('POST', '/login', ['email' => 'john@acme.test', 'password' => 'wrong password']);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Invalid email or password.', (string) $response->getBody());
        self::assertNull($this->session->get('account'));
    }

    public function testLoggingOut(): void
    {
        $this->request('POST', '/register', ['email' => 'john@acme.test', 'password' => 'correct horse']);

        $response = $this->request('POST', '/logout');

        self::assertSame(303, $response->getStatusCode());
        self::assertNull($this->session->get('account'));
        self::assertStringContainsString('<a href="/login">Log in</a>', (string) $this->request('GET', '/')->getBody());
    }
}
