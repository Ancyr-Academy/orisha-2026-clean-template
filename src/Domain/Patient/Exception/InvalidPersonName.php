<?php

declare(strict_types=1);

namespace Acme\Domain\Patient\Exception;

use DomainException;

final class InvalidPersonName extends DomainException
{
    public static function missing(string $part): self
    {
        return new self(sprintf('Please provide a %s.', $part));
    }

    public static function tooLong(string $part, int $maximumLength): self
    {
        return new self(sprintf('The %s cannot be longer than %d characters.', $part, $maximumLength));
    }
}
