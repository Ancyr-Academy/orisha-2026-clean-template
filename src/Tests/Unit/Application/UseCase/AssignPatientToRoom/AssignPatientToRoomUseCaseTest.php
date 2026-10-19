<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\AssignPatientToRoom;

use Acme\Application\UseCase\AssignPatientToRoom\AssignPatientToRoomCommand;
use Acme\Application\UseCase\AssignPatientToRoom\AssignPatientToRoomUseCase;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Domain\Room\Exception\PatientAlreadyAssigned;
use Acme\Domain\Room\Exception\RoomIsFull;
use Acme\Domain\Room\Exception\RoomNotFound;
use Acme\Domain\Room\Room;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use Acme\Tests\Unit\Double\InMemoryRoomRepository;
use PHPUnit\Framework\TestCase;

final class AssignPatientToRoomUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private InMemoryRoomRepository $rooms;
    private AssignPatientToRoomUseCase $useCase;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->rooms = new InMemoryRoomRepository();
        $this->useCase = new AssignPatientToRoomUseCase($this->patients, $this->rooms);
    }

    public function testItAssignsAPatientToARoomWithAFreeBed(): void
    {
        $room = $this->givenRoom('101', capacity: 2);
        $jane = $this->givenPatient('owner-id', 'Jane');

        $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', $room->id(), $jane->id()));

        self::assertSame([$jane->id()], $room->occupantIds());
    }

    public function testItRejectsAPatientWhenTheRoomIsFull(): void
    {
        $room = $this->givenRoom('101', capacity: 1);
        $jane = $this->givenPatient('owner-id', 'Jane');
        $john = $this->givenPatient('owner-id', 'John');
        $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', $room->id(), $jane->id()));

        try {
            $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', $room->id(), $john->id()));
            self::fail('A full room should reject the patient.');
        } catch (RoomIsFull) {
        }

        self::assertSame([$jane->id()], $room->occupantIds());
    }

    public function testItCannotUseAnotherAccountsRoom(): void
    {
        $room = $this->givenRoom('101', capacity: 2, ownerId: 'other-owner-id');
        $jane = $this->givenPatient('owner-id', 'Jane');

        try {
            $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', $room->id(), $jane->id()));
            self::fail("Another account's room should be out of reach.");
        } catch (RoomNotFound) {
        }

        self::assertSame([], $room->occupantIds());
    }

    public function testAPatientCannotBeInTwoRooms(): void
    {
        $first = $this->givenRoom('101', capacity: 2);
        $second = $this->givenRoom('102', capacity: 2);
        $jane = $this->givenPatient('owner-id', 'Jane');
        $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', $first->id(), $jane->id()));

        try {
            $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', $second->id(), $jane->id()));
            self::fail('A patient already in a room should be rejected.');
        } catch (PatientAlreadyAssigned $exception) {
            self::assertSame('This patient is already in room 101.', $exception->getMessage());
        }

        self::assertSame([], $second->occupantIds());
    }

    public function testItCannotAssignAnotherAccountsPatient(): void
    {
        $room = $this->givenRoom('101', capacity: 2);
        $alice = $this->givenPatient('other-owner-id', 'Alice');

        $this->expectException(PatientNotFound::class);

        $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', $room->id(), $alice->id()));
    }

    public function testItRejectsAnUnknownRoom(): void
    {
        $jane = $this->givenPatient('owner-id', 'Jane');

        $this->expectException(RoomNotFound::class);

        $this->useCase->execute(new AssignPatientToRoomCommand('owner-id', 'unknown-room-id', $jane->id()));
    }

    private function givenRoom(string $name, int $capacity, string $ownerId = 'owner-id'): Room
    {
        $room = Room::declare($this->rooms->nextIdentity(), $ownerId, $name, $capacity);
        $this->rooms->save($room);

        return $room;
    }

    private function givenPatient(string $ownerId, string $firstName): Patient
    {
        $patient = Patient::register(
            $this->patients->nextIdentity(),
            $ownerId,
            PersonName::of($firstName, 'Doe'),
            Gender::Unknown,
            BirthDate::fromString('1985-03-12'),
        );
        $this->patients->save($patient);

        return $patient;
    }
}
