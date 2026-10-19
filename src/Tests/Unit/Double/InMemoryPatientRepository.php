<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Double;

use Acme\Application\Ports\PatientRepository;
use Acme\Domain\Patient\Patient;
use Symfony\Component\Uid\Uuid;

final class InMemoryPatientRepository implements PatientRepository
{
    /** @var array<string, Patient> */
    private array $patients = [];

    public function nextIdentity(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public function save(Patient $patient): void
    {
        $this->patients[$patient->id()] = $patient;
    }

    public function remove(Patient $patient): void
    {
        unset($this->patients[$patient->id()]);
    }

    public function findOneOwnedBy(string $patientId, string $ownerId): ?Patient
    {
        $patient = $this->patients[$patientId] ?? null;

        return $patient?->ownerId() === $ownerId ? $patient : null;
    }

    public function findAllOwnedBy(string $ownerId): array
    {
        $patients = array_values(array_filter(
            $this->patients,
            static fn (Patient $patient): bool => $patient->ownerId() === $ownerId,
        ));

        usort($patients, static fn (Patient $a, Patient $b): int => [$a->name()->lastName, $a->name()->firstName]
            <=> [$b->name()->lastName, $b->name()->firstName]);

        return $patients;
    }
}
