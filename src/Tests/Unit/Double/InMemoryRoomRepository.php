<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Double;

use Acme\Application\Ports\RoomRepository;
use Acme\Domain\Room\Exception\RoomNameAlreadyInUse;
use Acme\Domain\Room\Room;
use Symfony\Component\Uid\Uuid;

/**
 * No locking needed: unit tests run in a single process.
 */
final class InMemoryRoomRepository implements RoomRepository
{
    /** @var array<string, Room> */
    private array $rooms = [];

    public function nextIdentity(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public function save(Room $room): void
    {
        $existing = $this->findOneByName($room->name(), $room->ownerId());

        if ($existing !== null && $existing->id() !== $room->id()) {
            throw new RoomNameAlreadyInUse($room->name());
        }

        $this->rooms[$room->id()] = $room;
    }

    public function findOneOwnedBy(string $roomId, string $ownerId): ?Room
    {
        $room = $this->rooms[$roomId] ?? null;

        return $room?->ownerId() === $ownerId ? $room : null;
    }

    public function findOneByOccupant(string $patientId): ?Room
    {
        foreach ($this->rooms as $room) {
            if ($room->hasOccupant($patientId)) {
                return $room;
            }
        }

        return null;
    }

    public function findOneByName(string $name, string $ownerId): ?Room
    {
        foreach ($this->rooms as $room) {
            if ($room->name() === $name && $room->ownerId() === $ownerId) {
                return $room;
            }
        }

        return null;
    }

    public function findAllOwnedBy(string $ownerId): array
    {
        $rooms = array_values(array_filter($this->rooms, static fn (Room $room): bool => $room->ownerId() === $ownerId));
        usort($rooms, static fn (Room $a, Room $b): int => $a->name() <=> $b->name());

        return $rooms;
    }
}
