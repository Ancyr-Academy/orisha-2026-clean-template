<?php

declare(strict_types=1);

namespace Acme\Application\Bus;

use LogicException;

final class UnknownUseCase extends LogicException
{
    public function __construct(object $input)
    {
        parent::__construct(sprintf('No use case is registered to handle "%s".', $input::class));
    }
}
