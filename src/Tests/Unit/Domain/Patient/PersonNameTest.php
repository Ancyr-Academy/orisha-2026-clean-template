<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Domain\Patient;

use Acme\Domain\Patient\Exception\InvalidPersonName;
use Acme\Domain\Patient\PersonName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PersonNameTest extends TestCase
{
    public function testItTrimsBothParts(): void
    {
        $name = PersonName::of('  Jane ', ' Doe  ');

        self::assertSame('Jane', $name->firstName);
        self::assertSame('Doe', $name->lastName);
        self::assertSame('Jane Doe', $name->fullName());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidNames(): iterable
    {
        yield 'missing first name' => [' ', 'Doe', 'Please provide a first name.'];
        yield 'missing last name' => ['Jane', '', 'Please provide a last name.'];
        yield 'first name too long' => [str_repeat('a', 101), 'Doe', 'The first name cannot be longer than 100 characters.'];
        yield 'last name too long' => ['Jane', str_repeat('é', 101), 'The last name cannot be longer than 100 characters.'];
    }

    #[DataProvider('invalidNames')]
    public function testItRejectsAnInvalidName(string $firstName, string $lastName, string $message): void
    {
        $this->expectException(InvalidPersonName::class);
        $this->expectExceptionMessage($message);

        PersonName::of($firstName, $lastName);
    }

    public function testItAcceptsAMultibyteNameAtTheMaximumLength(): void
    {
        self::assertSame(100, mb_strlen(PersonName::of(str_repeat('é', 100), 'Doe')->firstName));
    }
}
