<?php

declare(strict_types=1);

namespace Acme\Domain\Patient;

use Acme\Domain\Patient\Exception\InvalidGender;

/**
 * Administrative gender, using the values of the HL7 FHIR standard.
 */
enum Gender: string
{
    case Female = 'female';
    case Male = 'male';
    case Other = 'other';
    case Unknown = 'unknown';

    /**
     * @throws InvalidGender
     */
    public static function fromString(string $gender): self
    {
        return self::tryFrom($gender) ?? throw new InvalidGender($gender);
    }
}
