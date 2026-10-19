<?php

declare(strict_types=1);

namespace Acme\Domain\Patient;

/**
 * A patient owned by a single user (ownerId)
 */
final class Patient
{
    private function __construct(
        private readonly string $id,
        private readonly string $ownerId,
        private PersonName $name,
        private Gender $gender,
        private BirthDate $birthDate,
    ) {
    }

    public static function register(
        string $id,
        string $ownerId,
        PersonName $name,
        Gender $gender,
        BirthDate $birthDate,
    ): self {
        return new self($id, $ownerId, $name, $gender, $birthDate);
    }

    public function updateDetails(PersonName $name, Gender $gender, BirthDate $birthDate): void
    {
        $this->name = $name;
        $this->gender = $gender;
        $this->birthDate = $birthDate;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function name(): PersonName
    {
        return $this->name;
    }

    public function gender(): Gender
    {
        return $this->gender;
    }

    public function birthDate(): BirthDate
    {
        return $this->birthDate;
    }
}
