<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Domain\Room;

use Acme\Domain\Room\Exception\InvalidRoomCapacity;
use Acme\Domain\Room\Exception\InvalidRoomName;
use Acme\Domain\Room\Exception\PatientAlreadyAssigned;
use Acme\Domain\Room\Exception\PatientNotAssigned;
use Acme\Domain\Room\Exception\RoomIsFull;
use Acme\Domain\Room\Room;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RoomTest extends TestCase
{
    public function testADeclaredRoomIsEmpty(): void
    {
        $room = Room::declare('room-id', 'owner-id', ' 101 ', 2);

        self::assertSame('owner-id', $room->ownerId());
        self::assertSame('101', $room->name());
        self::assertSame(2, $room->capacity());
        self::assertSame([], $room->occupantIds());
        self::assertFalse($room->isFull());
    }

    /**
     * @return iterable<string, array{string, int, class-string<\Throwable>, string}>
     */
    public static function invalidRooms(): iterable
    {
        yield 'missing name' => ['  ', 1, InvalidRoomName::class, 'Please provide a room name.'];
        yield 'name too long' => [str_repeat('A', 21), 1, InvalidRoomName::class, 'The room name cannot be longer than 20 characters.'];
        yield 'no bed' => ['101', 0, InvalidRoomCapacity::class, 'A room must have at least one bed.'];
        yield 'negative capacity' => ['101', -1, InvalidRoomCapacity::class, 'A room must have at least one bed.'];
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidRooms')]
    public function testItRejectsAnInvalidDeclaration(string $name, int $capacity, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        Room::declare('room-id', 'owner-id', $name, $capacity);
    }

    public function testItAcceptsPatientsUpToItsCapacity(): void
    {
        $room = Room::declare('room-id', 'owner-id', '101', 2);

        $room->assign('jane-id');
        self::assertFalse($room->isFull());

        $room->assign('john-id');
        self::assertTrue($room->isFull());
        self::assertSame(['jane-id', 'john-id'], $room->occupantIds());
    }

    public function testAFullRoomRejectsAnotherPatient(): void
    {
        $room = Room::declare('room-id', 'owner-id', '101', 1);
        $room->assign('jane-id');

        try {
            $room->assign('john-id');
            self::fail('A full room should reject another patient.');
        } catch (RoomIsFull $exception) {
            self::assertSame('Room 101 is full.', $exception->getMessage());
        }

        self::assertSame(['jane-id'], $room->occupantIds());
    }

    public function testAPatientCannotTakeTwoBedsInTheSameRoom(): void
    {
        $room = Room::declare('room-id', 'owner-id', '101', 2);
        $room->assign('jane-id');

        $this->expectException(PatientAlreadyAssigned::class);

        $room->assign('jane-id');
    }

    public function testReleasingAPatientFreesTheirBed(): void
    {
        $room = Room::declare('room-id', 'owner-id', '101', 1);
        $room->assign('jane-id');

        $room->release('jane-id');
        $room->assign('john-id');

        self::assertSame(['john-id'], $room->occupantIds());
    }

    public function testItCannotReleaseAPatientWhoIsNotInTheRoom(): void
    {
        $room = Room::declare('room-id', 'owner-id', '101', 1);

        $this->expectException(PatientNotAssigned::class);

        $room->release('jane-id');
    }
}
