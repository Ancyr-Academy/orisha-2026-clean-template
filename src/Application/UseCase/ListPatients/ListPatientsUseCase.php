<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListPatients;

use Acme\Application\Ports\PatientRepository;
use Acme\Domain\Patient\Patient;
use DateTimeImmutable;

final readonly class ListPatientsUseCase
{
    public function __construct(
        private PatientRepository $patients,
    ) {
    }

    public function execute(ListPatientsQuery $query): ListPatientsResponse
    {
        $today = new DateTimeImmutable('today');

        return new ListPatientsResponse(array_map(
            static fn (Patient $patient): PatientListItem => new PatientListItem(
                $patient->id(),
                $patient->name()->firstName,
                $patient->name()->lastName,
                $patient->gender()->value,
                $patient->birthDate()->toString(),
                $patient->birthDate()->ageOn($today),
            ),
            $this->patients->findAllOwnedBy($query->ownerId),
        ));
    }
}
