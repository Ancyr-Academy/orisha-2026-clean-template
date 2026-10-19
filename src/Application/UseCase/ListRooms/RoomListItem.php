<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListRooms;

final readonly class RoomListItem
{
    /**
     * @param list<PatientSummary> $occupants
     */
    public function __construct(
        public string $id,
        public string $name,
        public int $capacity,
        public bool $full,
        public array $occupants,
    ) {
    }
}
