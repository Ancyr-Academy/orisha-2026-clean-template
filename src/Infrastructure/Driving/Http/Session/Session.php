<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Session;

interface Session
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value): void;

    /**
     * Issues a new session identifier, to be called whenever the privilege level changes (e.g. on login).
     */
    public function regenerate(): void;

    public function destroy(): void;
}
