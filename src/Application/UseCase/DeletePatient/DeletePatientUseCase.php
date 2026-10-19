<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\DeletePatient;

use Acme\Application\Ports\PatientRepository;
use Acme\Domain\Patient\Exception\PatientNotFound;

final readonly class DeletePatientUseCase
{
    public function __construct(
        private PatientRepository $patients,
    ) {
    }

    /**
     * @throws PatientNotFound
     */
    public function execute(DeletePatientCommand $command): DeletePatientResponse
    {
        $patient = $this->patients->findOneOwnedBy($command->patientId, $command->ownerId)
            ?? throw new PatientNotFound($command->patientId);

        $this->patients->remove($patient);

        return new DeletePatientResponse($patient->id());
    }
}
