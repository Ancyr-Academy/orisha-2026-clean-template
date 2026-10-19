<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListRooms;

use Acme\Application\Ports\PatientRepository;
use Acme\Application\Ports\RoomRepository;
use Acme\Domain\Room\Room;

final readonly class ListRoomsUseCase
{
    public function __construct(
        private RoomRepository $rooms,
        private PatientRepository $patients,
    ) {
    }

    public function execute(ListRoomsQuery $query): ListRoomsResponse
    {
        $rooms = $this->rooms->findAllOwnedBy($query->ownerId);

        /** @var array<string, PatientSummary> $patients */
        $patients = [];
        foreach ($this->patients->findAllOwnedBy($query->ownerId) as $patient) {
            $patients[$patient->id()] = new PatientSummary($patient->id(), $patient->name()->fullName());
        }

        $assignedIds = array_merge(...array_map(static fn (Room $room): array => $room->occupantIds(), $rooms));

        return new ListRoomsResponse(
            array_map(static fn (Room $room): RoomListItem => new RoomListItem(
                $room->id(),
                $room->name(),
                $room->capacity(),
                $room->isFull(),
                array_map(static fn (string $patientId): PatientSummary => $patients[$patientId], $room->occupantIds()),
            ), $rooms),
            array_values(array_diff_key($patients, array_flip($assignedIds))),
        );
    }
}
