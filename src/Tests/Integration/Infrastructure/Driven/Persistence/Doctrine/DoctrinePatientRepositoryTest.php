<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrinePatientRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrinePatientRepositoryTest extends DatabaseTestCase
{
    private DoctrinePatientRepository $repository;
    private string $ownerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrinePatientRepository($this->entityManager);
        $this->ownerId = Uuid::v7()->toRfc4122();
    }

    public function testItSavesAndFindsAPatientOfItsOwner(): void
    {
        $patient = $this->newPatient($this->ownerId, 'Jane', 'Doe');
        $this->repository->save($patient);
        $this->entityManager->clear();

        $found = $this->repository->findOneOwnedBy($patient->id(), $this->ownerId);

        self::assertNotNull($found);
        self::assertNotSame($patient, $found);
        self::assertSame($this->ownerId, $found->ownerId());
        self::assertEquals($patient->name(), $found->name());
        self::assertSame(Gender::Female, $found->gender());
        self::assertSame('1985-03-12', $found->birthDate()->toString());
    }

    public function testItDoesNotFindAPatientOfAnotherOwner(): void
    {
        $patient = $this->newPatient($this->ownerId, 'Jane', 'Doe');
        $this->repository->save($patient);

        self::assertNull($this->repository->findOneOwnedBy($patient->id(), Uuid::v7()->toRfc4122()));
    }

    public function testItReturnsNullForAMalformedId(): void
    {
        self::assertNull($this->repository->findOneOwnedBy('not-a-uuid', $this->ownerId));
    }

    public function testItListsTheOwnersPatientsSortedByName(): void
    {
        $this->repository->save($this->newPatient($this->ownerId, 'John', 'Doe'));
        $this->repository->save($this->newPatient($this->ownerId, 'Zoe', 'Adams'));
        $this->repository->save($this->newPatient(Uuid::v7()->toRfc4122(), 'Alice', 'Abbott'));
        $this->repository->save($this->newPatient($this->ownerId, 'Jane', 'Doe'));
        $this->entityManager->clear();

        self::assertSame(
            ['Zoe Adams', 'Jane Doe', 'John Doe'],
            array_map(
                static fn (Patient $patient): string => $patient->name()->fullName(),
                $this->repository->findAllOwnedBy($this->ownerId),
            ),
        );
    }

    public function testItPersistsUpdatedDetails(): void
    {
        $patient = $this->newPatient($this->ownerId, 'Jane', 'Doe');
        $this->repository->save($patient);

        $patient->updateDetails(PersonName::of('Jane', 'Smith'), Gender::Other, BirthDate::fromString('1986-04-13'));
        $this->repository->save($patient);
        $this->entityManager->clear();

        $found = $this->repository->findOneOwnedBy($patient->id(), $this->ownerId);

        self::assertNotNull($found);
        self::assertSame('Jane Smith', $found->name()->fullName());
        self::assertSame(Gender::Other, $found->gender());
        self::assertSame('1986-04-13', $found->birthDate()->toString());
    }

    public function testItRemovesAPatient(): void
    {
        $patient = $this->newPatient($this->ownerId, 'Jane', 'Doe');
        $this->repository->save($patient);

        $this->repository->remove($patient);
        $this->entityManager->clear();

        self::assertNull($this->repository->findOneOwnedBy($patient->id(), $this->ownerId));
    }

    private function newPatient(string $ownerId, string $firstName, string $lastName): Patient
    {
        return Patient::register(
            $this->repository->nextIdentity(),
            $ownerId,
            PersonName::of($firstName, $lastName),
            Gender::Female,
            BirthDate::fromString('1985-03-12'),
        );
    }
}
