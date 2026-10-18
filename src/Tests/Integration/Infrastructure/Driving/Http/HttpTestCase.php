<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driving\Http;

use Acme\Application\Transaction\TransactionManager;
use Acme\Domain\Account\AccountRepository;
use Acme\Infrastructure\Driving\Http\HttpApplication;
use Acme\Infrastructure\Driving\Http\Session\Session;
use Acme\Tests\Integration\Double\ArraySession;
use Acme\Tests\Unit\Double\InMemoryAccountRepository;
use Acme\Tests\Unit\Double\SpyTransactionManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class HttpTestCase extends TestCase
{
    protected InMemoryAccountRepository $accounts;
    protected ArraySession $session;
    protected SpyTransactionManager $transactions;
    private App $app;

    protected function setUp(): void
    {
        $this->accounts = new InMemoryAccountRepository();
        $this->session = new ArraySession();
        $this->transactions = new SpyTransactionManager();

        $container = (require dirname(__DIR__, 6) . '/config/container.php')([
            'app.log_errors' => false, // 404/405 responses are expected here: keep the test output clean
            AccountRepository::class => $this->accounts,
            Session::class => $this->session,
            TransactionManager::class => $this->transactions,
        ]);

        $this->app = HttpApplication::create($container);
    }

    /**
     * @param array<string, string> $form
     */
    protected function request(string $method, string $uri, array $form = []): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if ($form !== []) {
            $request = $request
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withParsedBody($form);
        }

        return $this->app->handle($request);
    }
}
