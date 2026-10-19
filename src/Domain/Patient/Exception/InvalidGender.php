<?php

declare(strict_types=1);

namespace Acme\Domain\Patient\Exception;

use DomainException;

final class InvalidGender extends DomainException
{
    public function __construct(public readonly string $gender)
    {
        parent::__construct('Please select a valid gender.');
    }
}
