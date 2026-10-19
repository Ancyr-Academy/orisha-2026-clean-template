<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\GetPatient;

final readonly class GetPatientResponse
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $gender,
        public string $birthDate,
    ) {
    }
}
