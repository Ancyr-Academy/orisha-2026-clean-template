<?php

declare(strict_types=1);

namespace Acme\Domain\Patient;

use Acme\Domain\Patient\Exception\InvalidBirthDate;
use DateTimeImmutable;

final readonly class BirthDate
{
    private const string FORMAT = 'Y-m-d';

    private function __construct(
        public DateTimeImmutable $value,
    ) {
    }

    /**
     * @param string $date an ISO 8601 calendar date (YYYY-MM-DD)
     *
     * @throws InvalidBirthDate
     */
    public static function fromString(string $date): self
    {
        $date = trim($date);
        $value = DateTimeImmutable::createFromFormat('!' . self::FORMAT, $date);

        // Formatting back rejects dates PHP would silently shift, such as 2026-02-30
        if ($value === false || $value->format(self::FORMAT) !== $date) {
            throw InvalidBirthDate::malformed($date);
        }

        if ($value > new DateTimeImmutable('today')) {
            throw InvalidBirthDate::inTheFuture($date);
        }

        return new self($value);
    }

    public function ageOn(DateTimeImmutable $date): int
    {
        return $this->value->diff($date)->y;
    }

    public function toString(): string
    {
        return $this->value->format(self::FORMAT);
    }
}
