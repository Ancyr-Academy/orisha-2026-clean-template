<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\Login;

use DomainException;

final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid email or password.');
    }
}
