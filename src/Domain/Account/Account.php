<?php

declare(strict_types=1);

namespace Acme\Domain\Account;

use DateTimeImmutable;

final class Account
{
    private function __construct(
        private readonly string $id,
        private readonly Email $email,
        private readonly string $passwordHash,
        private readonly DateTimeImmutable $registeredAt,
    ) {
    }

    public static function register(
        string $id,
        Email $email,
        string $passwordHash,
        DateTimeImmutable $registeredAt,
    ): self {
        return new self($id, $email, $passwordHash, $registeredAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
