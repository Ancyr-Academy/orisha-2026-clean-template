<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\ListRooms;

final readonly class ListRoomsQuery
{
    public function __construct(
        public string $ownerId,
    ) {
    }
}
