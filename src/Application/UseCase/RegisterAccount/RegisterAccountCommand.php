<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\RegisterAccount;

final readonly class RegisterAccountCommand
{
    public function __construct(
        public string $email,
        #[\SensitiveParameter]
        public string $password,
    ) {
    }
}
