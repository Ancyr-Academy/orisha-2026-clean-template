<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListRooms;

final readonly class ListRoomsResponse
{
    /**
     * @param list<RoomListItem> $rooms
     * @param list<PatientSummary> $unassignedPatients the patients who are not in any room yet
     */
    public function __construct(
        public array $rooms,
        public array $unassignedPatients,
    ) {
    }
}
