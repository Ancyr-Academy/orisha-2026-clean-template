<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\Bus;

use Acme\Application\Bus\DirectUseCaseBus;
use Acme\Application\Bus\TransactionalUseCaseBus;
use Acme\Tests\Unit\Double\SpyTransactionManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class TransactionalUseCaseBusTest extends TestCase
{
    private SpyTransactionManager $transactions;

    protected function setUp(): void
    {
        $this->transactions = new SpyTransactionManager();
    }

    public function testItExecutesTheUseCaseInsideATransaction(): void
    {
        $transactions = $this->transactions;
        $useCase = new class ($transactions) {
            public function __construct(private SpyTransactionManager $transactions)
            {
            }

            public function execute(stdClass $input): stdClass
            {
                $response = new stdClass();
                $response->inTransaction = $this->transactions->inTransaction;

                return $response;
            }
        };

        $response = $this->busFor($useCase)->execute(new stdClass());

        self::assertTrue($response->inTransaction);
        self::assertSame(1, $this->transactions->commits);
        self::assertSame(0, $this->transactions->rollbacks);
    }

    public function testItRollsBackWhenTheUseCaseFails(): void
    {
        $useCase = new class () {
            public function execute(stdClass $input): never
            {
                throw new RuntimeException('Use case failed');
            }
        };

        try {
            $this->busFor($useCase)->execute(new stdClass());
            self::fail('The exception thrown by the use case should be rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Use case failed', $exception->getMessage());
        }

        self::assertSame(0, $this->transactions->commits);
        self::assertSame(1, $this->transactions->rollbacks);
    }

    private function busFor(object $useCase): TransactionalUseCaseBus
    {
        return new TransactionalUseCaseBus(
            new DirectUseCaseBus([stdClass::class => static fn (): object => $useCase]),
            $this->transactions,
        );
    }
}
