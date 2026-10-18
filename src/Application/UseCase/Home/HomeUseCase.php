<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\Home;

final class HomeUseCase
{
    public function execute(HomeQuery $query): HomeResponse
    {
        return new HomeResponse('Hello World');
    }
}
