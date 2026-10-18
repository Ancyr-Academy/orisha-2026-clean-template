<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\Bus;

use Acme\Application\Bus\DirectUseCaseBus;
use Acme\Application\Bus\UnknownUseCase;
use Acme\Application\UseCase\Home\HomeQuery;
use Acme\Application\UseCase\Home\HomeUseCase;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DirectUseCaseBusTest extends TestCase
{
    public function testItExecutesTheUseCaseHandlingTheInput(): void
    {
        $bus = new DirectUseCaseBus([HomeQuery::class => static fn (): HomeUseCase => new HomeUseCase()]);

        $response = $bus->execute(new HomeQuery());

        self::assertSame('Hello World', $response->message);
    }

    public function testItRejectsAnInputNoUseCaseHandles(): void
    {
        $bus = new DirectUseCaseBus([]);

        $this->expectException(UnknownUseCase::class);
        $this->expectExceptionMessage('No use case is registered to handle "stdClass".');

        $bus->execute(new stdClass());
    }
}
