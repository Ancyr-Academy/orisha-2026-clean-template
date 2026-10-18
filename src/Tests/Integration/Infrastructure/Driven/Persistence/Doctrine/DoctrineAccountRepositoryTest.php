<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Domain\Account\Account;
use Acme\Domain\Account\Email;
use Acme\Domain\Account\Exception\EmailAlreadyInUse;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrineAccountRepository;
use DateTimeImmutable;

final class DoctrineAccountRepositoryTest extends DatabaseTestCase
{
    private DoctrineAccountRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineAccountRepository($this->entityManager);
    }

    public function testItSavesAndFindsAnAccountByEmail(): void
    {
        $account = $this->newAccount('john@acme.test');
        $this->repository->save($account);
        $this->entityManager->clear();

        $found = $this->repository->findByEmail(Email::fromString('John@Acme.test'));

        self::assertNotNull($found);
        self::assertNotSame($account, $found);
        self::assertSame($account->id(), $found->id());
        self::assertTrue($account->email()->equals($found->email()));
        self::assertSame($account->passwordHash(), $found->passwordHash());
        self::assertEquals($account->registeredAt(), $found->registeredAt());
    }

    public function testItReturnsNullForAnUnknownEmail(): void
    {
        self::assertNull($this->repository->findByEmail(Email::fromString('nobody@acme.test')));
    }

    public function testTheDatabaseEnforcesEmailUniqueness(): void
    {
        $this->repository->save($this->newAccount('john@acme.test'));
        $this->entityManager->clear();

        $this->expectException(EmailAlreadyInUse::class);

        $this->repository->save($this->newAccount('john@acme.test'));
    }

    private function newAccount(string $email): Account
    {
        return Account::register(
            $this->repository->nextIdentity(),
            Email::fromString($email),
            password_hash('correct horse', PASSWORD_DEFAULT),
            new DateTimeImmutable('2026-10-17 12:00:00'),
        );
    }
}
