<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\CreatePatient;

final readonly class CreatePatientResponse
{
    public function __construct(
        public string $patientId,
    ) {
    }
}
