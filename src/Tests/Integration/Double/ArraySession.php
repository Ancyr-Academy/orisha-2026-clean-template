<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Double;

use Acme\Infrastructure\Driving\Http\Session\Session;

final class ArraySession implements Session
{
    /** @var array<string, mixed> */
    private array $data = [];

    public int $regenerations = 0;

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function regenerate(): void
    {
        ++$this->regenerations;
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}
