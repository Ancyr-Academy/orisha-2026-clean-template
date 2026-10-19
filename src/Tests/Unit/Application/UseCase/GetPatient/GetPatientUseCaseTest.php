<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\GetPatient;

use Acme\Application\UseCase\GetPatient\GetPatientQuery;
use Acme\Application\UseCase\GetPatient\GetPatientResponse;
use Acme\Application\UseCase\GetPatient\GetPatientUseCase;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use PHPUnit\Framework\TestCase;

final class GetPatientUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private GetPatientUseCase $useCase;
    private Patient $patient;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->useCase = new GetPatientUseCase($this->patients);

        $this->patient = Patient::register(
            $this->patients->nextIdentity(),
            'owner-id',
            PersonName::of('Jane', 'Doe'),
            Gender::Female,
            BirthDate::fromString('1985-03-12'),
        );
        $this->patients->save($this->patient);
    }

    public function testItReturnsThePatientDetails(): void
    {
        $response = $this->useCase->execute(new GetPatientQuery('owner-id', $this->patient->id()));

        self::assertEquals(new GetPatientResponse($this->patient->id(), 'Jane', 'Doe', 'female', '1985-03-12'), $response);
    }

    public function testItHidesAnotherAccountsPatient(): void
    {
        $this->expectException(PatientNotFound::class);

        $this->useCase->execute(new GetPatientQuery('intruder-id', $this->patient->id()));
    }
}
