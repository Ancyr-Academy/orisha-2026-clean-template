<?php

declare(strict_types=1);

namespace Acme\Application\Ports;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;

    public function verify(string $plainPassword, string $hash): bool;
}
