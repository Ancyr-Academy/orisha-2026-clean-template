<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\Home;

use Acme\Application\UseCase\Home\HomeQuery;
use Acme\Application\UseCase\Home\HomeUseCase;
use PHPUnit\Framework\TestCase;

final class HomeUseCaseTest extends TestCase
{
    public function testItReturnsHelloWorld(): void
    {
        $response = (new HomeUseCase())->execute(new HomeQuery());

        self::assertSame('Hello World', $response->message);
    }
}
