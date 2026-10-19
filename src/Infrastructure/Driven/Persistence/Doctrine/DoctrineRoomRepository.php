<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Application\Ports\RoomRepository;
use Acme\Domain\Room\Exception\RoomNameAlreadyInUse;
use Acme\Domain\Room\Room;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineRoomRepository implements RoomRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function nextIdentity(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public function save(Room $room): void
    {
        $this->entityManager->persist($room);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new RoomNameAlreadyInUse($room->name());
        }
    }

    public function findOneOwnedBy(string $roomId, string $ownerId): ?Room
    {
        // Ids come from URLs: PostgreSQL would reject a malformed UUID instead of finding nothing
        if (!Uuid::isValid($roomId) || !Uuid::isValid($ownerId)) {
            return null;
        }

        // SELECT ... FOR UPDATE: concurrent transactions wait for this one to end before reading the room
        return $this->entityManager
            ->createQuery('SELECT r FROM ' . Room::class . ' r WHERE r.id = :id AND r.ownerId = :ownerId')
            ->setParameters(['id' => $roomId, 'ownerId' => $ownerId])
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findOneByOccupant(string $patientId): ?Room
    {
        // The occupant ids are stored as a JSONB array: "@>" tells whether it contains the patient id.
        // Locking here matters: after waiting for a lock, PostgreSQL re-checks the condition on the latest data.
        $room = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT id, owner_id FROM rooms WHERE occupant_ids @> CAST(:occupant AS JSONB) FOR UPDATE',
            ['occupant' => json_encode([$patientId], JSON_THROW_ON_ERROR)],
        );

        return $room === false ? null : $this->findOneOwnedBy($room['id'], $room['owner_id']);
    }

    public function findOneByName(string $name, string $ownerId): ?Room
    {
        if (!Uuid::isValid($ownerId)) {
            return null;
        }

        return $this->entityManager->getRepository(Room::class)->findOneBy(['name' => $name, 'ownerId' => $ownerId]);
    }

    public function findAllOwnedBy(string $ownerId): array
    {
        if (!Uuid::isValid($ownerId)) {
            return [];
        }

        return array_values($this->entityManager
            ->getRepository(Room::class)
            ->findBy(['ownerId' => $ownerId], ['name' => 'ASC']));
    }
}
