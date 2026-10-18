<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\Home;

final readonly class HomeResponse
{
    public function __construct(
        public string $message,
    ) {
    }
}
