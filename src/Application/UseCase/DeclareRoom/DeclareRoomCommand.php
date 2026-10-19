<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\DeclareRoom;

final readonly class DeclareRoomCommand
{
    public function __construct(
        public string $ownerId,
        public string $name,
        public int $capacity,
    ) {
    }
}
