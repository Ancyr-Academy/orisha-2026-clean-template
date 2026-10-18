<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\RegisterAccount;

use DomainException;

final class PasswordTooShort extends DomainException
{
    public function __construct(public readonly int $minimumLength)
    {
        parent::__construct(sprintf('Your password must be at least %d characters long.', $minimumLength));
    }
}
