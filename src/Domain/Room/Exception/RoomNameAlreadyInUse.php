<?php

declare(strict_types=1);

namespace Acme\Domain\Room\Exception;

use DomainException;

final class RoomNameAlreadyInUse extends DomainException
{
    public function __construct(public readonly string $roomName)
    {
        parent::__construct(sprintf('A room named %s already exists.', $roomName));
    }
}
