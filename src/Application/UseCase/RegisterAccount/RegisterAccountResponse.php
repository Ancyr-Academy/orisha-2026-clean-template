<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\RegisterAccount;

final readonly class RegisterAccountResponse
{
    public function __construct(
        public string $accountId,
        public string $email,
    ) {
    }
}
