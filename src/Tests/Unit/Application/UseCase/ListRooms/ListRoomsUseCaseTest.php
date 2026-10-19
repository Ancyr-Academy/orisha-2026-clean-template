<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\ListRooms;

use Acme\Application\UseCase\ListRooms\ListRoomsQuery;
use Acme\Application\UseCase\ListRooms\ListRoomsUseCase;
use Acme\Application\UseCase\ListRooms\PatientSummary;
use Acme\Application\UseCase\ListRooms\RoomListItem;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Domain\Room\Room;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use Acme\Tests\Unit\Double\InMemoryRoomRepository;
use PHPUnit\Framework\TestCase;

final class ListRoomsUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private InMemoryRoomRepository $rooms;
    private ListRoomsUseCase $useCase;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->rooms = new InMemoryRoomRepository();
        $this->useCase = new ListRoomsUseCase($this->rooms, $this->patients);
    }

    public function testItListsTheAccountsRoomsWithTheirOccupants(): void
    {
        $jane = $this->givenPatient('owner-id', 'Jane');
        $john = $this->givenPatient('owner-id', 'John');
        $zoe = $this->givenPatient('owner-id', 'Zoe');
        $double = $this->givenRoom('owner-id', '102', 2, $jane, $john);
        $single = $this->givenRoom('owner-id', '101', 1);
        $this->givenRoom('other-owner-id', '100', 1, $this->givenPatient('other-owner-id', 'Alice'));

        $response = $this->useCase->execute(new ListRoomsQuery('owner-id'));

        self::assertEquals([
            new RoomListItem($single->id(), '101', 1, false, []),
            new RoomListItem($double->id(), '102', 2, true, [
                new PatientSummary($jane->id(), 'Jane Doe'),
                new PatientSummary($john->id(), 'John Doe'),
            ]),
        ], $response->rooms);
        self::assertEquals([new PatientSummary($zoe->id(), 'Zoe Doe')], $response->unassignedPatients);
    }

    public function testWithoutRoomsEveryPatientIsUnassigned(): void
    {
        $jane = $this->givenPatient('owner-id', 'Jane');

        $response = $this->useCase->execute(new ListRoomsQuery('owner-id'));

        self::assertSame([], $response->rooms);
        self::assertEquals([new PatientSummary($jane->id(), 'Jane Doe')], $response->unassignedPatients);
    }

    private function givenRoom(string $ownerId, string $name, int $capacity, Patient ...$occupants): Room
    {
        $room = Room::declare($this->rooms->nextIdentity(), $ownerId, $name, $capacity);
        foreach ($occupants as $occupant) {
            $room->assign($occupant->id());
        }
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
