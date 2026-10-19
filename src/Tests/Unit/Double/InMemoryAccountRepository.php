<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Double;

use Acme\Application\Ports\AccountRepository;
use Acme\Domain\Account\Account;
use Acme\Domain\Account\Email;
use Acme\Domain\Account\Exception\EmailAlreadyInUse;
use Symfony\Component\Uid\Uuid;

final class InMemoryAccountRepository implements AccountRepository
{
    /** @var array<string, Account> */
    private array $accounts = [];

    public function nextIdentity(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public function save(Account $account): void
    {
        $existing = $this->findByEmail($account->email());

        if ($existing !== null && $existing->id() !== $account->id()) {
            throw new EmailAlreadyInUse($account->email());
        }

        $this->accounts[$account->id()] = $account;
    }

    public function findByEmail(Email $email): ?Account
    {
        foreach ($this->accounts as $account) {
            if ($account->email()->equals($email)) {
                return $account;
            }
        }

        return null;
    }
}
