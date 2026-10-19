<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Domain\Patient;

use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\InvalidBirthDate;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BirthDateTest extends TestCase
{
    public function testItParsesAnIsoDate(): void
    {
        $birthDate = BirthDate::fromString('1985-03-12');

        self::assertSame('1985-03-12', $birthDate->toString());
        self::assertSame('00:00:00', $birthDate->value->format('H:i:s'));
    }

    public function testTodayIsAValidBirthDate(): void
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        self::assertSame($today, BirthDate::fromString($today)->toString());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedDates(): iterable
    {
        yield 'empty' => [''];
        yield 'not a date' => ['yesterday'];
        yield 'wrong format' => ['12/03/1985'];
        yield 'impossible day' => ['2025-02-30'];
        yield 'missing zero padding' => ['1985-3-12'];
    }

    #[DataProvider('malformedDates')]
    public function testItRejectsAMalformedDate(string $date): void
    {
        $this->expectException(InvalidBirthDate::class);
        $this->expectExceptionMessage('Please provide a valid birth date.');

        BirthDate::fromString($date);
    }

    public function testItRejectsADateInTheFuture(): void
    {
        $this->expectException(InvalidBirthDate::class);
        $this->expectExceptionMessage('The birth date cannot be in the future.');

        BirthDate::fromString((new DateTimeImmutable('tomorrow'))->format('Y-m-d'));
    }

    public function testItComputesTheAgeOnAGivenDate(): void
    {
        $birthDate = BirthDate::fromString('1985-03-12');

        self::assertSame(40, $birthDate->ageOn(new DateTimeImmutable('2026-03-11')));
        self::assertSame(41, $birthDate->ageOn(new DateTimeImmutable('2026-03-12')));
    }
}
