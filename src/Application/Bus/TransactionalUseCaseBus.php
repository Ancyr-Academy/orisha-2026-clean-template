<?php

declare(strict_types=1);

namespace Acme\Application\Bus;

use Acme\Application\Transaction\TransactionManager;

final readonly class TransactionalUseCaseBus implements UseCaseBus
{
    public function __construct(
        private UseCaseBus $inner,
        private TransactionManager $transactionManager,
    ) {
    }

    public function execute(object $input): object
    {
        return $this->transactionManager->transactional(fn (): object => $this->inner->execute($input));
    }
}
