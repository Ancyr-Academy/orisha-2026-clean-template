<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\DeletePatient;

use Acme\Application\UseCase\DeletePatient\DeletePatientCommand;
use Acme\Application\UseCase\DeletePatient\DeletePatientUseCase;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Domain\Room\Room;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use Acme\Tests\Unit\Double\InMemoryRoomRepository;
use PHPUnit\Framework\TestCase;

final class DeletePatientUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private InMemoryRoomRepository $rooms;
    private DeletePatientUseCase $useCase;
    private Patient $patient;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->rooms = new InMemoryRoomRepository();
        $this->useCase = new DeletePatientUseCase($this->patients, $this->rooms);

        $this->patient = Patient::register(
            $this->patients->nextIdentity(),
            'owner-id',
            PersonName::of('Jane', 'Doe'),
            Gender::Female,
            BirthDate::fromString('1985-03-12'),
        );
        $this->patients->save($this->patient);
    }

    public function testItDeletesThePatient(): void
    {
        $this->useCase->execute(new DeletePatientCommand('owner-id', $this->patient->id()));

        self::assertNull($this->patients->findOneOwnedBy($this->patient->id(), 'owner-id'));
    }

    public function testDeletingAPatientFreesTheirBed(): void
    {
        $room = Room::declare($this->rooms->nextIdentity(), 'owner-id', '101', 1);
        $room->assign($this->patient->id());
        $this->rooms->save($room);

        $this->useCase->execute(new DeletePatientCommand('owner-id', $this->patient->id()));

        self::assertSame([], $room->occupantIds());
        self::assertFalse($room->isFull());
    }

    public function testItCannotDeleteAnotherAccountsPatient(): void
    {
        try {
            $this->useCase->execute(new DeletePatientCommand('intruder-id', $this->patient->id()));
            self::fail('Another account should not be able to delete the patient.');
        } catch (PatientNotFound) {
        }

        self::assertNotNull($this->patients->findOneOwnedBy($this->patient->id(), 'owner-id'));
    }

    public function testItRejectsAnUnknownPatient(): void
    {
        $this->expectException(PatientNotFound::class);

        $this->useCase->execute(new DeletePatientCommand('owner-id', 'unknown-id'));
    }
}
