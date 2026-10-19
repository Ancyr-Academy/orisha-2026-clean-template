<?php

declare(strict_types=1);

namespace Acme\Domain\Patient;

use Acme\Domain\Patient\Exception\InvalidPersonName;

final readonly class PersonName
{
    public const int MAXIMUM_LENGTH = 100;

    private function __construct(
        public string $firstName,
        public string $lastName,
    ) {
    }

    /**
     * @throws InvalidPersonName
     */
    public static function of(string $firstName, string $lastName): self
    {
        return new self(self::part($firstName, 'first name'), self::part($lastName, 'last name'));
    }

    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    private static function part(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '') {
            throw InvalidPersonName::missing($label);
        }

        if (mb_strlen($value) > self::MAXIMUM_LENGTH) {
            throw InvalidPersonName::tooLong($label, self::MAXIMUM_LENGTH);
        }

        return $value;
    }
}
