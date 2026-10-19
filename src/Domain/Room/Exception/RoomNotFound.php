<?php

declare(strict_types=1);

namespace Acme\Domain\Room\Exception;

use DomainException;

final class RoomNotFound extends DomainException
{
    public function __construct(public readonly string $roomId)
    {
        parent::__construct('This room does not exist.');
    }
}
