<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\GetPatient;

final readonly class GetPatientQuery
{
    public function __construct(
        public string $ownerId,
        public string $patientId,
    ) {
    }
}
