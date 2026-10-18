<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Double;

use Acme\Application\Transaction\TransactionManager;
use Throwable;

final class SpyTransactionManager implements TransactionManager
{
    public bool $inTransaction = false;
    public int $commits = 0;
    public int $rollbacks = 0;

    public function transactional(callable $operation): mixed
    {
        $this->inTransaction = true;

        try {
            $result = $operation();
            ++$this->commits;

            return $result;
        } catch (Throwable $exception) {
            ++$this->rollbacks;

            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }
}
