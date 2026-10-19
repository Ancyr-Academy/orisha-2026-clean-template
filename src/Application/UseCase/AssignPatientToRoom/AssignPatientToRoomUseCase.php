<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\AssignPatientToRoom;

use Acme\Application\Ports\PatientRepository;
use Acme\Application\Ports\RoomRepository;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Room\Exception\PatientAlreadyAssigned;
use Acme\Domain\Room\Exception\RoomIsFull;
use Acme\Domain\Room\Exception\RoomNotFound;

final readonly class AssignPatientToRoomUseCase
{
    public function __construct(
        private PatientRepository $patients,
        private RoomRepository $rooms,
    ) {
    }

    /**
     * @throws PatientNotFound
     * @throws PatientAlreadyAssigned
     * @throws RoomNotFound
     * @throws RoomIsFull
     */
    public function execute(AssignPatientToRoomCommand $command): AssignPatientToRoomResponse
    {
        // An account can only place its own patients in its own rooms
        $patient = $this->patients->findOneOwnedBy($command->patientId, $command->ownerId)
            ?? throw new PatientNotFound($command->patientId);

        // "One room per patient" spans several rooms, so no single Room can check it: the use case does
        $currentRoom = $this->rooms->findOneByOccupant($patient->id());
        if ($currentRoom !== null) {
            throw new PatientAlreadyAssigned($patient->id(), $currentRoom->name());
        }

        $room = $this->rooms->findOneOwnedBy($command->roomId, $command->ownerId)
            ?? throw new RoomNotFound($command->roomId);

        // The capacity, on the other hand, is the room's own invariant
        $room->assign($patient->id());

        $this->rooms->save($room);

        return new AssignPatientToRoomResponse($room->id(), $patient->id());
    }
}
