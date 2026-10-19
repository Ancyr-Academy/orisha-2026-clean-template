<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\DeletePatient;

final readonly class DeletePatientResponse
{
    public function __construct(
        public string $patientId,
    ) {
    }
}
