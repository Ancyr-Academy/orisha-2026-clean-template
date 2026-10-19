<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListPatients;

final readonly class ListPatientsResponse
{
    /**
     * @param list<PatientListItem> $patients
     */
    public function __construct(
        public array $patients,
    ) {
    }
}
