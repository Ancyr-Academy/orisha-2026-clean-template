<?php

declare(strict_types=1);

namespace Acme\Domain\Patient\Exception;

use DomainException;

final class InvalidBirthDate extends DomainException
{
    private function __construct(public readonly string $date, string $message)
    {
        parent::__construct($message);
    }

    public static function malformed(string $date): self
    {
        return new self($date, 'Please provide a valid birth date.');
    }

    public static function inTheFuture(string $date): self
    {
        return new self($date, 'The birth date cannot be in the future.');
    }
}
