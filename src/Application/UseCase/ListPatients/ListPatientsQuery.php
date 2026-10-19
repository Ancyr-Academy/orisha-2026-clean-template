<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListPatients;

final readonly class ListPatientsQuery
{
    public function __construct(
        public string $ownerId,
    ) {
    }
}
