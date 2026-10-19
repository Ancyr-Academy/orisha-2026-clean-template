<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Application\Ports\PatientRepository;
use Acme\Domain\Patient\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrinePatientRepository implements PatientRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function nextIdentity(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public function save(Patient $patient): void
    {
        $this->entityManager->persist($patient);
        $this->entityManager->flush();
    }

    public function remove(Patient $patient): void
    {
        $this->entityManager->remove($patient);
        $this->entityManager->flush();
    }

    public function findOneOwnedBy(string $patientId, string $ownerId): ?Patient
    {
        // Ids come from URLs: PostgreSQL would reject a malformed UUID instead of finding nothing
        if (!Uuid::isValid($patientId) || !Uuid::isValid($ownerId)) {
            return null;
        }

        return $this->entityManager
            ->getRepository(Patient::class)
            ->findOneBy(['id' => $patientId, 'ownerId' => $ownerId]);
    }

    public function findAllOwnedBy(string $ownerId): array
    {
        if (!Uuid::isValid($ownerId)) {
            return [];
        }

        return array_values($this->entityManager
            ->getRepository(Patient::class)
            ->findBy(['ownerId' => $ownerId], ['name.lastName' => 'ASC', 'name.firstName' => 'ASC']));
    }
}
