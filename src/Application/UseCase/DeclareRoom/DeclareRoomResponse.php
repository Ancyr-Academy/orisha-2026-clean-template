<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\DeclareRoom;

final readonly class DeclareRoomResponse
{
    public function __construct(
        public string $roomId,
    ) {
    }
}
