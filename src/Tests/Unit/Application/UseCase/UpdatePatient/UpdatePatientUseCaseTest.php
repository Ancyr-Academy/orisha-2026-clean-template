<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\UpdatePatient;

use Acme\Application\UseCase\UpdatePatient\UpdatePatientCommand;
use Acme\Application\UseCase\UpdatePatient\UpdatePatientUseCase;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\InvalidBirthDate;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use PHPUnit\Framework\TestCase;

final class UpdatePatientUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private UpdatePatientUseCase $useCase;
    private Patient $patient;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->useCase = new UpdatePatientUseCase($this->patients);

        $this->patient = Patient::register(
            $this->patients->nextIdentity(),
            'owner-id',
            PersonName::of('Jane', 'Doe'),
            Gender::Female,
            BirthDate::fromString('1985-03-12'),
        );
        $this->patients->save($this->patient);
    }

    public function testItUpdatesThePatientDetails(): void
    {
        $response = $this->useCase->execute(
            new UpdatePatientCommand('owner-id', $this->patient->id(), 'Jane', 'Smith', 'other', '1986-04-13'),
        );

        $patient = $this->patients->findOneOwnedBy($this->patient->id(), 'owner-id');

        self::assertSame($this->patient->id(), $response->patientId);
        self::assertNotNull($patient);
        self::assertSame('Jane Smith', $patient->name()->fullName());
        self::assertSame(Gender::Other, $patient->gender());
        self::assertSame('1986-04-13', $patient->birthDate()->toString());
    }

    public function testItCannotUpdateAnotherAccountsPatient(): void
    {
        $this->expectException(PatientNotFound::class);

        $this->useCase->execute(
            new UpdatePatientCommand('intruder-id', $this->patient->id(), 'Jane', 'Smith', 'other', '1986-04-13'),
        );
    }

    public function testInvalidDetailsLeaveThePatientUnchanged(): void
    {
        try {
            $this->useCase->execute(
                new UpdatePatientCommand('owner-id', $this->patient->id(), 'Janet', 'Doe', 'female', '2025-02-30'),
            );
            self::fail('An invalid birth date should be rejected.');
        } catch (InvalidBirthDate) {
        }

        self::assertSame('Jane Doe', $this->patient->name()->fullName());
    }
}
