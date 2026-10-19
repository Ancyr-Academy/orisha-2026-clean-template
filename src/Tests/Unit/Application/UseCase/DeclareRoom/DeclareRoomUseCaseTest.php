<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\DeclareRoom;

use Acme\Application\UseCase\DeclareRoom\DeclareRoomCommand;
use Acme\Application\UseCase\DeclareRoom\DeclareRoomUseCase;
use Acme\Domain\Room\Exception\InvalidRoomCapacity;
use Acme\Domain\Room\Exception\RoomNameAlreadyInUse;
use Acme\Tests\Unit\Double\InMemoryRoomRepository;
use PHPUnit\Framework\TestCase;

final class DeclareRoomUseCaseTest extends TestCase
{
    private InMemoryRoomRepository $rooms;
    private DeclareRoomUseCase $useCase;

    protected function setUp(): void
    {
        $this->rooms = new InMemoryRoomRepository();
        $this->useCase = new DeclareRoomUseCase($this->rooms);
    }

    public function testItDeclaresARoomOwnedByTheAccount(): void
    {
        $response = $this->useCase->execute(new DeclareRoomCommand('owner-id', '101', 2));

        $room = $this->rooms->findOneOwnedBy($response->roomId, 'owner-id');

        self::assertNotNull($room);
        self::assertSame('101', $room->name());
        self::assertSame(2, $room->capacity());
    }

    public function testRoomNamesAreUniqueWithinAnAccount(): void
    {
        $this->useCase->execute(new DeclareRoomCommand('owner-id', '101', 2));

        $this->expectException(RoomNameAlreadyInUse::class);

        $this->useCase->execute(new DeclareRoomCommand('owner-id', ' 101', 1));
    }

    public function testAnotherAccountMayUseTheSameRoomName(): void
    {
        $this->useCase->execute(new DeclareRoomCommand('owner-id', '101', 2));
        $this->useCase->execute(new DeclareRoomCommand('other-owner-id', '101', 1));

        self::assertCount(1, $this->rooms->findAllOwnedBy('owner-id'));
        self::assertCount(1, $this->rooms->findAllOwnedBy('other-owner-id'));
    }

    public function testItRejectsARoomWithoutBeds(): void
    {
        try {
            $this->useCase->execute(new DeclareRoomCommand('owner-id', '101', 0));
            self::fail('A room without beds should be rejected.');
        } catch (InvalidRoomCapacity) {
        }

        self::assertSame([], $this->rooms->findAllOwnedBy('owner-id'));
    }
}
