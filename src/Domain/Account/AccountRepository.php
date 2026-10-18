<?php

declare(strict_types=1);

namespace Acme\Domain\Account;

use Acme\Domain\Account\Exception\EmailAlreadyInUse;

interface AccountRepository
{
    public function nextIdentity(): string;

    /**
     * @throws EmailAlreadyInUse
     */
    public function save(Account $account): void;

    public function findByEmail(Email $email): ?Account;
}
