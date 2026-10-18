<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Domain\Account\Account;
use Acme\Domain\Account\AccountRepository;
use Acme\Domain\Account\Email;
use Acme\Domain\Account\Exception\EmailAlreadyInUse;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAccountRepository implements AccountRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function nextIdentity(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public function save(Account $account): void
    {
        $this->entityManager->persist($account);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new EmailAlreadyInUse($account->email());
        }
    }

    public function findByEmail(Email $email): ?Account
    {
        return $this->entityManager
            ->getRepository(Account::class)
            ->findOneBy(['email.value' => $email->value]);
    }
}
