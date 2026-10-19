<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\CreatePatient;

final readonly class CreatePatientCommand
{
    public function __construct(
        public string $ownerId,
        public string $firstName,
        public string $lastName,
        public string $gender,
        public string $birthDate,
    ) {
    }
}
