<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\UnassignPatientFromRoom;

use Acme\Application\Ports\PatientRepository;
use Acme\Application\Ports\RoomRepository;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Room\Exception\PatientNotAssigned;
use Acme\Domain\Room\Exception\RoomNotFound;

final readonly class UnassignPatientFromRoomUseCase
{
    public function __construct(
        private PatientRepository $patients,
        private RoomRepository $rooms,
    ) {
    }

    /**
     * @throws PatientNotFound
     * @throws RoomNotFound
     * @throws PatientNotAssigned
     */
    public function execute(UnassignPatientFromRoomCommand $command): UnassignPatientFromRoomResponse
    {
        $patient = $this->patients->findOneOwnedBy($command->patientId, $command->ownerId)
            ?? throw new PatientNotFound($command->patientId);

        $room = $this->rooms->findOneOwnedBy($command->roomId, $command->ownerId)
            ?? throw new RoomNotFound($command->roomId);
        $room->release($patient->id());

        $this->rooms->save($room);

        return new UnassignPatientFromRoomResponse($room->id(), $patient->id());
    }
}
