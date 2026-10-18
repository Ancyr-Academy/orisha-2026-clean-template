<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\Login;

use Acme\Application\UseCase\Login\InvalidCredentials;
use Acme\Application\UseCase\Login\LoginCommand;
use Acme\Application\UseCase\Login\LoginUseCase;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountCommand;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountUseCase;
use Acme\Infrastructure\Driven\Security\NativePasswordHasher;
use Acme\Tests\Unit\Double\InMemoryAccountRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoginUseCaseTest extends TestCase
{
    private LoginUseCase $useCase;
    private string $accountId;

    protected function setUp(): void
    {
        $accounts = new InMemoryAccountRepository();
        $passwordHasher = new NativePasswordHasher();

        $this->accountId = new RegisterAccountUseCase($accounts, $passwordHasher)
            ->execute(new RegisterAccountCommand('john@acme.test', 'correct horse'))
            ->accountId;

        $this->useCase = new LoginUseCase($accounts, $passwordHasher);
    }

    public function testItLogsInWithValidCredentials(): void
    {
        $response = $this->useCase->execute(new LoginCommand(' John@Acme.test', 'correct horse'));

        self::assertSame($this->accountId, $response->accountId);
        self::assertSame('john@acme.test', $response->email);
    }

    #[DataProvider('invalidCredentials')]
    public function testItRejectsInvalidCredentials(string $email, string $password): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->useCase->execute(new LoginCommand($email, $password));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidCredentials(): iterable
    {
        yield 'wrong password' => ['john@acme.test', 'wrong password'];
        yield 'unknown email' => ['jane@acme.test', 'correct horse'];
        yield 'malformed email' => ['john', 'correct horse'];
    }
}
