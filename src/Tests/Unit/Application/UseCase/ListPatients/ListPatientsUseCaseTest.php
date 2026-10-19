<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\ListPatients;

use Acme\Application\UseCase\ListPatients\ListPatientsQuery;
use Acme\Application\UseCase\ListPatients\ListPatientsUseCase;
use Acme\Application\UseCase\ListPatients\PatientListItem;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ListPatientsUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private ListPatientsUseCase $useCase;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->useCase = new ListPatientsUseCase($this->patients);
    }

    public function testItListsOnlyTheAccountsPatientsSortedByName(): void
    {
        $this->givenPatient('owner-id', 'John', 'Doe');
        $this->givenPatient('owner-id', 'Zoe', 'Adams');
        $this->givenPatient('other-owner-id', 'Alice', 'Abbott');
        $this->givenPatient('owner-id', 'Jane', 'Doe');

        $response = $this->useCase->execute(new ListPatientsQuery('owner-id'));

        self::assertSame(
            ['Adams, Zoe', 'Doe, Jane', 'Doe, John'],
            array_map(static fn (PatientListItem $patient): string => $patient->lastName . ', ' . $patient->firstName, $response->patients),
        );
    }

    public function testItExposesThePatientDetailsWithTheirAge(): void
    {
        // January 1st keeps the expected age stable whatever the current date (even on February 29th)
        $birthDate = (new DateTimeImmutable('first day of january this year'))->modify('-30 years');
        $patient = $this->givenPatient('owner-id', 'Jane', 'Doe', $birthDate->format('Y-m-d'));

        $item = $this->useCase->execute(new ListPatientsQuery('owner-id'))->patients[0];

        self::assertEquals(new PatientListItem($patient->id(), 'Jane', 'Doe', 'female', $birthDate->format('Y-m-d'), 30), $item);
    }

    private function givenPatient(string $ownerId, string $firstName, string $lastName, string $birthDate = '1985-03-12'): Patient
    {
        $patient = Patient::register(
            $this->patients->nextIdentity(),
            $ownerId,
            PersonName::of($firstName, $lastName),
            Gender::Female,
            BirthDate::fromString($birthDate),
        );
        $this->patients->save($patient);

        return $patient;
    }
}
