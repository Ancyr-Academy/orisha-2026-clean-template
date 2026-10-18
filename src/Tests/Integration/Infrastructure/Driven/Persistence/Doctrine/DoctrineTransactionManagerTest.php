<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Domain\Account\Account;
use Acme\Domain\Account\Email;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrineAccountRepository;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrineTransactionManager;
use DateTimeImmutable;
use RuntimeException;

final class DoctrineTransactionManagerTest extends DatabaseTestCase
{
    private DoctrineAccountRepository $repository;
    private DoctrineTransactionManager $transactionManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineAccountRepository($this->entityManager);
        $this->transactionManager = new DoctrineTransactionManager($this->entityManager);
    }

    public function testItCommitsAndReturnsTheResult(): void
    {
        $result = $this->transactionManager->transactional(function (): string {
            $this->repository->save($this->newAccount('john@acme.test'));

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertFalse($this->entityManager->getConnection()->isTransactionActive());
        self::assertSame(1, $this->countAccounts());
    }

    public function testItRollsBackEverythingWhenTheOperationThrows(): void
    {
        try {
            $this->transactionManager->transactional(function (): never {
                $this->repository->save($this->newAccount('john@acme.test'));
                $this->repository->save($this->newAccount('jane@acme.test'));

                throw new RuntimeException('Something went wrong after saving');
            });
            self::fail('The exception thrown by the operation should be rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Something went wrong after saving', $exception->getMessage());
        }

        self::assertFalse($this->entityManager->getConnection()->isTransactionActive());
        self::assertSame(0, $this->countAccounts());
    }

    public function testTheEntityManagerRemainsUsableAfterARollback(): void
    {
        try {
            $this->transactionManager->transactional(function (): never {
                $this->repository->save($this->newAccount('john@acme.test'));

                throw new RuntimeException('Rolled back');
            });
        } catch (RuntimeException) {
        }

        self::assertTrue($this->entityManager->isOpen());
        self::assertNull($this->repository->findByEmail(Email::fromString('john@acme.test')));

        $this->transactionManager->transactional(fn () => $this->repository->save($this->newAccount('john@acme.test')));

        self::assertSame(1, $this->countAccounts());
    }

    private function countAccounts(): int
    {
        return (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM accounts');
    }

    private function newAccount(string $email): Account
    {
        return Account::register(
            $this->repository->nextIdentity(),
            Email::fromString($email),
            password_hash('correct horse', PASSWORD_DEFAULT),
            new DateTimeImmutable(),
        );
    }
}
