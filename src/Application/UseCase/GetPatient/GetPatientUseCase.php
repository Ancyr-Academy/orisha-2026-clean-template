<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\GetPatient;

use Acme\Application\Ports\PatientRepository;
use Acme\Domain\Patient\Exception\PatientNotFound;

final readonly class GetPatientUseCase
{
    public function __construct(
        private PatientRepository $patients,
    ) {
    }

    /**
     * @throws PatientNotFound
     */
    public function execute(GetPatientQuery $query): GetPatientResponse
    {
        $patient = $this->patients->findOneOwnedBy($query->patientId, $query->ownerId)
            ?? throw new PatientNotFound($query->patientId);

        return new GetPatientResponse(
            $patient->id(),
            $patient->name()->firstName,
            $patient->name()->lastName,
            $patient->gender()->value,
            $patient->birthDate()->toString(),
        );
    }
}
