<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\UpdatePatient;

final readonly class UpdatePatientCommand
{
    public function __construct(
        public string $ownerId,
        public string $patientId,
        public string $firstName,
        public string $lastName,
        public string $gender,
        public string $birthDate,
    ) {
    }
}
