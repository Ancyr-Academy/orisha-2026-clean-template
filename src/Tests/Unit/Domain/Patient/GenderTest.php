<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Domain\Patient;

use Acme\Domain\Patient\Exception\InvalidGender;
use Acme\Domain\Patient\Gender;
use PHPUnit\Framework\TestCase;

final class GenderTest extends TestCase
{
    public function testItParsesAKnownValue(): void
    {
        self::assertSame(Gender::Female, Gender::fromString('female'));
    }

    public function testItRejectsAnUnknownValue(): void
    {
        $this->expectException(InvalidGender::class);

        Gender::fromString('Female');
    }
}
