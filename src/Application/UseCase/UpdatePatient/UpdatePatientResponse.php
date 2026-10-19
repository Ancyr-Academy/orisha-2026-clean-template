<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\UpdatePatient;

final readonly class UpdatePatientResponse
{
    public function __construct(
        public string $patientId,
    ) {
    }
}
