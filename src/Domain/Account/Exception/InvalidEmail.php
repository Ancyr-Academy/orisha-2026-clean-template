<?php

declare(strict_types=1);

namespace Acme\Domain\Account\Exception;

use DomainException;

final class InvalidEmail extends DomainException
{
    public function __construct(public readonly string $email)
    {
        parent::__construct('Please provide a valid email address.');
    }
}
