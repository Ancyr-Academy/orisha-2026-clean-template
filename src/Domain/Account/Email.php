<?php

declare(strict_types=1);

namespace Acme\Domain\Account;

use Acme\Domain\Account\Exception\InvalidEmail;

final readonly class Email
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $email): self
    {
        $normalized = mb_strtolower(trim($email));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmail($email);
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
