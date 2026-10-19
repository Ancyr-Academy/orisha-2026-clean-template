<?php

declare(strict_types=1);

namespace Acme\Domain\Room\Exception;

use DomainException;

final class PatientAlreadyAssigned extends DomainException
{
    public function __construct(public readonly string $patientId, public readonly string $roomName)
    {
        parent::__construct(sprintf('This patient is already in room %s.', $roomName));
    }
}
