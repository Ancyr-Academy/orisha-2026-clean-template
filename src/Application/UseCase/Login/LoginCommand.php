<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\Login;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        #[\SensitiveParameter]
        public string $password,
    ) {
    }
}
