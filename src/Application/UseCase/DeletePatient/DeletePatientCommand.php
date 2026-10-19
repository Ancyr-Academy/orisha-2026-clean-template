<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\DeletePatient;

final readonly class DeletePatientCommand
{
    public function __construct(
        public string $ownerId,
        public string $patientId,
    ) {
    }
}
