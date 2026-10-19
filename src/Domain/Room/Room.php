<?php

declare(strict_types=1);

namespace Acme\Domain\Room;

use Acme\Domain\Room\Exception\InvalidRoomCapacity;
use Acme\Domain\Room\Exception\InvalidRoomName;
use Acme\Domain\Room\Exception\PatientAlreadyAssigned;
use Acme\Domain\Room\Exception\PatientNotAssigned;
use Acme\Domain\Room\Exception\RoomIsFull;

/**
 * A hospital room, owned by one account (its owner), referenced by id like a patient's owner.
 *
 * The room is the consistency boundary of its capacity: patients can only enter it through assign(), which refuses
 * them once every bed is taken. That is why the room keeps its occupants, rather than each patient keeping its room.
 * The capacity is set when the room is declared and never changes.
 */
final class Room
{
    public const int MAXIMUM_NAME_LENGTH = 20;

    /**
     * @param list<string> $occupantIds
     */
    private function __construct(
        private readonly string $id,
        private readonly string $ownerId,
        private readonly string $name,
        private readonly int $capacity,
        private array $occupantIds,
    ) {
    }

    /**
     * @throws InvalidRoomName
     * @throws InvalidRoomCapacity
     */
    public static function declare(string $id, string $ownerId, string $name, int $capacity): self
    {
        $name = trim($name);

        if ($name === '') {
            throw InvalidRoomName::missing();
        }

        if (mb_strlen($name) > self::MAXIMUM_NAME_LENGTH) {
            throw InvalidRoomName::tooLong(self::MAXIMUM_NAME_LENGTH);
        }

        if ($capacity < 1) {
            throw new InvalidRoomCapacity($capacity);
        }

        return new self($id, $ownerId, $name, $capacity, []);
    }

    /**
     * @throws PatientAlreadyAssigned
     * @throws RoomIsFull
     */
    public function assign(string $patientId): void
    {
        if ($this->hasOccupant($patientId)) {
            throw new PatientAlreadyAssigned($patientId, $this->name);
        }

        if ($this->isFull()) {
            throw new RoomIsFull($this->name);
        }

        $this->occupantIds[] = $patientId;
    }

    /**
     * @throws PatientNotAssigned
     */
    public function release(string $patientId): void
    {
        if (!$this->hasOccupant($patientId)) {
            throw new PatientNotAssigned($patientId, $this->name);
        }

        $this->occupantIds = array_values(array_diff($this->occupantIds, [$patientId]));
    }

    public function hasOccupant(string $patientId): bool
    {
        return in_array($patientId, $this->occupantIds, true);
    }

    public function isFull(): bool
    {
        return count($this->occupantIds) >= $this->capacity;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }

    /**
     * @return list<string>
     */
    public function occupantIds(): array
    {
        return $this->occupantIds;
    }
}
