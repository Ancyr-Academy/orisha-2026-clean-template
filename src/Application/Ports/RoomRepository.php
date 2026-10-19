<?php

declare(strict_types=1);

namespace Acme\Application\Ports;

use Acme\Domain\Room\Exception\RoomNameAlreadyInUse;
use Acme\Domain\Room\Room;

/**
 * Like patients, rooms are always looked up for a given owner: an account can never load another account's room.
 *
 * The room enforces its capacity on the patients it knows about, but two concurrent requests (two browser tabs, two
 * people sharing an account...) could each load it with one free bed left and both take it. Methods loading a room to
 * modify it therefore lock it until the current transaction ends (use cases always run in one, see
 * TransactionalUseCaseBus): the second request waits, then sees the room as full.
 */
interface RoomRepository
{
    public function nextIdentity(): string;

    /**
     * @throws RoomNameAlreadyInUse
     */
    public function save(Room $room): void;

    /**
     * Loads a room to modify it, locked until the end of the current transaction.
     */
    public function findOneOwnedBy(string $roomId, string $ownerId): ?Room;

    /**
     * Loads the room a patient is assigned to, locked until the end of the current transaction.
     * A patient can only be in a room of their own owner, so the patient id is enough to scope the lookup.
     */
    public function findOneByOccupant(string $patientId): ?Room;

    public function findOneByName(string $name, string $ownerId): ?Room;

    /**
     * @return list<Room> sorted by name
     */
    public function findAllOwnedBy(string $ownerId): array;
}
