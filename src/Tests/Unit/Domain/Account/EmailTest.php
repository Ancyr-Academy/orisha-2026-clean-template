<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Domain\Account;

use Acme\Domain\Account\Email;
use Acme\Domain\Account\Exception\InvalidEmail;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testItNormalizesTheAddress(): void
    {
        self::assertSame('john@acme.test', Email::fromString('  John@ACME.test ')->value);
    }

    public function testItRejectsAnInvalidAddress(): void
    {
        $this->expectException(InvalidEmail::class);

        Email::fromString('not-an-email');
    }
}
