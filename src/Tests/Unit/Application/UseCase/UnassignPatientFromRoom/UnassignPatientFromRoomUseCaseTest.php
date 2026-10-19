<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\UnassignPatientFromRoom;

use Acme\Application\UseCase\UnassignPatientFromRoom\UnassignPatientFromRoomCommand;
use Acme\Application\UseCase\UnassignPatientFromRoom\UnassignPatientFromRoomUseCase;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Domain\Room\Exception\PatientNotAssigned;
use Acme\Domain\Room\Room;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use Acme\Tests\Unit\Double\InMemoryRoomRepository;
use PHPUnit\Framework\TestCase;

final class UnassignPatientFromRoomUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private InMemoryRoomRepository $rooms;
    private UnassignPatientFromRoomUseCase $useCase;
    private Room $room;
    private Patient $patient;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->rooms = new InMemoryRoomRepository();
        $this->useCase = new UnassignPatientFromRoomUseCase($this->patients, $this->rooms);

        $this->patient = Patient::register(
            $this->patients->nextIdentity(),
            'owner-id',
            PersonName::of('Jane', 'Doe'),
            Gender::Female,
            BirthDate::fromString('1985-03-12'),
        );
        $this->patients->save($this->patient);

        $this->room = Room::declare($this->rooms->nextIdentity(), 'owner-id', '101', 1);
        $this->room->assign($this->patient->id());
        $this->rooms->save($this->room);
    }

    public function testItFreesThePatientsBed(): void
    {
        $this->useCase->execute(new UnassignPatientFromRoomCommand('owner-id', $this->room->id(), $this->patient->id()));

        self::assertSame([], $this->room->occupantIds());
    }

    public function testItCannotUnassignAnotherAccountsPatient(): void
    {
        try {
            $this->useCase->execute(new UnassignPatientFromRoomCommand('intruder-id', $this->room->id(), $this->patient->id()));
            self::fail('Another account should not be able to move the patient.');
        } catch (PatientNotFound) {
        }

        self::assertSame([$this->patient->id()], $this->room->occupantIds());
    }

    public function testItRejectsAPatientWhoIsNotInTheRoom(): void
    {
        $otherRoom = Room::declare($this->rooms->nextIdentity(), 'owner-id', '102', 1);
        $this->rooms->save($otherRoom);

        $this->expectException(PatientNotAssigned::class);

        $this->useCase->execute(new UnassignPatientFromRoomCommand('owner-id', $otherRoom->id(), $this->patient->id()));
    }
}
