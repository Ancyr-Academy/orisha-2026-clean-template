<?php

declare(strict_types=1);

namespace Acme\Application\Transaction;

interface TransactionManager
{
    /**
     * Runs the operation in a transaction: commits when it returns, rolls back and rethrows when it throws.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
