<?php

declare(strict_types=1);

namespace Acme\Domain\Patient\Exception;

use DomainException;

/**
 * Also raised for a patient owned by another account, so that its existence is not disclosed.
 */
final class PatientNotFound extends DomainException
{
    public function __construct(public readonly string $patientId)
    {
        parent::__construct('This patient does not exist.');
    }
}
