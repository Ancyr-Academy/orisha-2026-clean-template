<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Domain\Patient;

use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use PHPUnit\Framework\TestCase;

final class PatientTest extends TestCase
{
    public function testItUpdatesTheDetailsButKeepsItsIdentityAndOwner(): void
    {
        $patient = Patient::register(
            'patient-id',
            'owner-id',
            PersonName::of('Jane', 'Doe'),
            Gender::Female,
            BirthDate::fromString('1985-03-12'),
        );

        $patient->updateDetails(PersonName::of('Jane', 'Smith'), Gender::Other, BirthDate::fromString('1986-04-13'));

        self::assertSame('patient-id', $patient->id());
        self::assertSame('owner-id', $patient->ownerId());
        self::assertSame('Jane Smith', $patient->name()->fullName());
        self::assertSame(Gender::Other, $patient->gender());
        self::assertSame('1986-04-13', $patient->birthDate()->toString());
    }
}
