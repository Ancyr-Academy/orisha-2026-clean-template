<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\Login;

final readonly class LoginResponse
{
    public function __construct(
        public string $accountId,
        public string $email,
    ) {
    }
}
