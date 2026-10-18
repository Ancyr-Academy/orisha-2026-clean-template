<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Security;

use Acme\Domain\Account\PasswordHasher;

final readonly class NativePasswordHasher implements PasswordHasher
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function verify(#[\SensitiveParameter] string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }
}
