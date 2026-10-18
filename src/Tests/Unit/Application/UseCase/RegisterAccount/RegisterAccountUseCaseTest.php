<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\RegisterAccount;

use Acme\Application\UseCase\RegisterAccount\PasswordTooShort;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountCommand;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountUseCase;
use Acme\Domain\Account\Email;
use Acme\Domain\Account\Exception\EmailAlreadyInUse;
use Acme\Domain\Account\Exception\InvalidEmail;
use Acme\Infrastructure\Driven\Security\NativePasswordHasher;
use Acme\Tests\Unit\Double\InMemoryAccountRepository;
use PHPUnit\Framework\TestCase;

final class RegisterAccountUseCaseTest extends TestCase
{
    private InMemoryAccountRepository $accounts;
    private NativePasswordHasher $passwordHasher;
    private RegisterAccountUseCase $useCase;

    protected function setUp(): void
    {
        $this->accounts = new InMemoryAccountRepository();
        $this->passwordHasher = new NativePasswordHasher();
        $this->useCase = new RegisterAccountUseCase($this->accounts, $this->passwordHasher);
    }

    public function testItRegistersAnAccountWithAHashedPassword(): void
    {
        $response = $this->useCase->execute(new RegisterAccountCommand('John@Acme.test', 'correct horse'));

        $account = $this->accounts->findByEmail(Email::fromString('john@acme.test'));

        self::assertNotNull($account);
        self::assertSame($account->id(), $response->accountId);
        self::assertSame('john@acme.test', $response->email);
        self::assertNotSame('correct horse', $account->passwordHash());
        self::assertTrue($this->passwordHasher->verify('correct horse', $account->passwordHash()));
    }

    public function testItRejectsAnEmailAlreadyInUse(): void
    {
        $this->useCase->execute(new RegisterAccountCommand('john@acme.test', 'correct horse'));

        $this->expectException(EmailAlreadyInUse::class);

        $this->useCase->execute(new RegisterAccountCommand('JOHN@acme.test', 'another password'));
    }

    public function testItRejectsAnInvalidEmail(): void
    {
        $this->expectException(InvalidEmail::class);

        $this->useCase->execute(new RegisterAccountCommand('john', 'correct horse'));
    }

    public function testItRejectsAPasswordThatIsTooShort(): void
    {
        $this->expectException(PasswordTooShort::class);

        $this->useCase->execute(new RegisterAccountCommand('john@acme.test', 'short'));
    }
}
