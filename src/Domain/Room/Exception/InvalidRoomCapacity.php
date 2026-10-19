<?php

declare(strict_types=1);

namespace Acme\Domain\Room\Exception;

use DomainException;

final class InvalidRoomCapacity extends DomainException
{
    public function __construct(public readonly int $capacity)
    {
        parent::__construct('A room must have at least one bed.');
    }
}
