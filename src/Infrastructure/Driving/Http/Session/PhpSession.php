<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Session;

final class PhpSession implements Session
{
    public function get(string $key): mixed
    {
        if (!$this->exists()) {
            return null;
        }

        $this->start();

        return $_SESSION[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();

        $_SESSION[$key] = $value;
    }

    public function regenerate(): void
    {
        $this->start();

        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        if (!$this->exists()) {
            return;
        }

        $this->start();

        $_SESSION = [];
        session_destroy();

        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => 1,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }

    /**
     * Avoids starting a session (and sending a cookie) for anonymous visitors that never had one.
     */
    private function exists(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[session_name()]);
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start([
            'use_strict_mode' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => !in_array($_SERVER['HTTPS'] ?? '', ['', 'off'], true),
        ]);
    }
}
