<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListPatients;

final readonly class PatientListItem
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $gender,
        public string $birthDate,
        public int $age,
    ) {
    }
}
