<?php

declare(strict_types=1);

namespace Acme\Domain\Room\Exception;

use DomainException;

final class RoomIsFull extends DomainException
{
    public function __construct(public readonly string $roomName)
    {
        parent::__construct(sprintf('Room %s is full.', $roomName));
    }
}
