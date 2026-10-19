<?php

declare(strict_types=1);

namespace Acme\Application\Ports;

use Acme\Domain\Account\Account;
use Acme\Domain\Account\Email;
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
