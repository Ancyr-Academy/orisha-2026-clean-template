<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListRooms;

final readonly class PatientSummary
{
    public function __construct(
        public string $id,
        public string $fullName,
    ) {
    }
}
