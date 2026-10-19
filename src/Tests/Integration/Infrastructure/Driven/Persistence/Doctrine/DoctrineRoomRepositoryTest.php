<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Domain\Room\Exception\RoomNameAlreadyInUse;
use Acme\Domain\Room\Room;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrineRoomRepository;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrineTransactionManager;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Uid\Uuid;

final class DoctrineRoomRepositoryTest extends DatabaseTestCase
{
    private DoctrineRoomRepository $repository;
    private DoctrineTransactionManager $transactions;
    private string $ownerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineRoomRepository($this->entityManager);
        $this->transactions = new DoctrineTransactionManager($this->entityManager);
        $this->ownerId = Uuid::v7()->toRfc4122();
    }

    public function testItSavesARoomWithItsOccupants(): void
    {
        $room = $this->givenRoom($this->ownerId, '101', 2, $jane = Uuid::v7()->toRfc4122());
        $this->entityManager->clear();

        // Loading a room for modification locks it, which requires a transaction (as in every use case)
        $found = $this->transactions->transactional(fn () => $this->repository->findOneOwnedBy($room->id(), $this->ownerId));

        self::assertNotNull($found);
        self::assertNotSame($room, $found);
        self::assertSame($this->ownerId, $found->ownerId());
        self::assertSame('101', $found->name());
        self::assertSame(2, $found->capacity());
        self::assertSame([$jane], $found->occupantIds());
    }

    public function testItDoesNotFindARoomOfAnotherOwner(): void
    {
        $room = $this->givenRoom($this->ownerId, '101', 1);

        $found = $this->transactions->transactional(fn () => $this->repository->findOneOwnedBy($room->id(), Uuid::v7()->toRfc4122()));

        self::assertNull($found);
    }

    public function testItPersistsReleasedBeds(): void
    {
        $room = $this->givenRoom($this->ownerId, '101', 2, $jane = Uuid::v7()->toRfc4122(), $john = Uuid::v7()->toRfc4122());

        $room->release($jane);
        $this->repository->save($room);
        $this->entityManager->clear();

        self::assertSame([$john], $this->repository->findOneByName('101', $this->ownerId)?->occupantIds());
    }

    public function testItFindsTheRoomOfAPatient(): void
    {
        $this->givenRoom($this->ownerId, '101', 2, Uuid::v7()->toRfc4122());
        $room = $this->givenRoom($this->ownerId, '102', 2, Uuid::v7()->toRfc4122(), $jane = Uuid::v7()->toRfc4122());
        $this->entityManager->clear();

        $found = $this->transactions->transactional(fn () => $this->repository->findOneByOccupant($jane));
        $none = $this->transactions->transactional(fn () => $this->repository->findOneByOccupant(Uuid::v7()->toRfc4122()));

        self::assertSame($room->id(), $found?->id());
        self::assertNull($none);
    }

    public function testItListsTheOwnersRoomsSortedByName(): void
    {
        $this->givenRoom($this->ownerId, 'B12', 1);
        $this->givenRoom(Uuid::v7()->toRfc4122(), 'A00', 1);
        $this->givenRoom($this->ownerId, 'A01', 2);
        $this->entityManager->clear();

        self::assertSame(
            ['A01', 'B12'],
            array_map(static fn (Room $room): string => $room->name(), $this->repository->findAllOwnedBy($this->ownerId)),
        );
    }

    public function testTheDatabaseEnforcesRoomNameUniquenessWithinAnAccount(): void
    {
        $this->givenRoom($this->ownerId, '101', 1);
        $this->givenRoom(Uuid::v7()->toRfc4122(), '101', 1);
        $this->entityManager->clear();

        $this->expectException(RoomNameAlreadyInUse::class);

        $this->repository->save(Room::declare($this->repository->nextIdentity(), $this->ownerId, '101', 2));
    }

    public function testItReturnsNullForAMalformedId(): void
    {
        self::assertNull($this->repository->findOneOwnedBy('not-a-uuid', $this->ownerId));
    }

    /**
     * Without this lock, two requests could both see the last free bed and both take it.
     */
    public function testALoadedRoomStaysLockedUntilTheTransactionEnds(): void
    {
        $room = $this->givenRoom($this->ownerId, '101', 1);
        $this->entityManager->getConnection()->beginTransaction();
        $this->repository->findOneOwnedBy($room->id(), $this->ownerId);

        // A second connection plays a concurrent request, giving up after 100 ms instead of waiting for the lock
        $concurrent = self::createEntityManager();
        $concurrent->getConnection()->executeStatement("SET lock_timeout = '100ms'");
        $concurrent->getConnection()->beginTransaction();

        try {
            (new DoctrineRoomRepository($concurrent))->findOneOwnedBy($room->id(), $this->ownerId);
            self::fail('The concurrent request should have waited for the lock.');
        } catch (DriverException $exception) {
            self::assertSame('55P03', $exception->getSQLState(), 'lock_not_available');
        } finally {
            $concurrent->getConnection()->rollBack();
            $concurrent->close();
            $this->entityManager->getConnection()->rollBack();
        }
    }

    private function givenRoom(string $ownerId, string $name, int $capacity, string ...$occupantIds): Room
    {
        $room = Room::declare($this->repository->nextIdentity(), $ownerId, $name, $capacity);
        foreach ($occupantIds as $occupantId) {
            $room->assign($occupantId);
        }
        $this->repository->save($room);

        return $room;
    }
}
