<?php

declare(strict_types=1);

namespace Acme\Domain\Account\Exception;

use Acme\Domain\Account\Email;
use DomainException;

final class EmailAlreadyInUse extends DomainException
{
    public function __construct(public readonly Email $email)
    {
        parent::__construct('An account already exists with this email address.');
    }
}
