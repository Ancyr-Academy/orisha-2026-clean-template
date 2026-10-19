<?php

declare(strict_types=1);

namespace Acme\Domain\Room\Exception;

use DomainException;

final class PatientNotAssigned extends DomainException
{
    public function __construct(public readonly string $patientId, public readonly string $roomName)
    {
        parent::__construct(sprintf('This patient is not in room %s.', $roomName));
    }
}
