<?php

declare(strict_types=1);

namespace Acme\Domain\Room\Exception;

use DomainException;

final class InvalidRoomName extends DomainException
{
    public static function missing(): self
    {
        return new self('Please provide a room name.');
    }

    public static function tooLong(int $maximumLength): self
    {
        return new self(sprintf('The room name cannot be longer than %d characters.', $maximumLength));
    }
}
