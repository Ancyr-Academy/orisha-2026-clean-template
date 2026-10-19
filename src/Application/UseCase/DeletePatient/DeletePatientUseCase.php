<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\DeletePatient;

use Acme\Application\Ports\PatientRepository;
use Acme\Application\Ports\RoomRepository;
use Acme\Domain\Patient\Exception\PatientNotFound;

final readonly class DeletePatientUseCase
{
    public function __construct(
        private PatientRepository $patients,
        private RoomRepository $rooms,
    ) {
    }

    /**
     * @throws PatientNotFound
     */
    public function execute(DeletePatientCommand $command): DeletePatientResponse
    {
        $patient = $this->patients->findOneOwnedBy($command->patientId, $command->ownerId)
            ?? throw new PatientNotFound($command->patientId);

        // A deleted patient frees their bed, in the same transaction
        $room = $this->rooms->findOneByOccupant($patient->id());
        if ($room !== null) {
            $room->release($patient->id());
            $this->rooms->save($room);
        }

        $this->patients->remove($patient);

        return new DeletePatientResponse($patient->id());
    }
}
