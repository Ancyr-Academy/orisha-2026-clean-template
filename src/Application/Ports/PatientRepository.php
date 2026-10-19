<?php

declare(strict_types=1);

namespace Acme\Application\Ports;

use Acme\Domain\Patient\Patient;

/**
 * Every lookup is scoped to an owner: an account can never load another account's patients.
 */
interface PatientRepository
{
    public function nextIdentity(): string;

    public function save(Patient $patient): void;

    public function remove(Patient $patient): void;

    public function findOneOwnedBy(string $patientId, string $ownerId): ?Patient;

    /**
     * @return list<Patient> sorted by last name, then first name
     */
    public function findAllOwnedBy(string $ownerId): array;
}
