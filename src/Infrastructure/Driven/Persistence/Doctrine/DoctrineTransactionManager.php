<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Application\Transaction\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Unlike EntityManager::wrapInTransaction(), does not close the entity manager when the operation throws:
 * use cases throw expected exceptions (e.g. EmailAlreadyInUse) after which the request goes on.
 */
final readonly class DoctrineTransactionManager implements TransactionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $operation): mixed
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $result = $operation();

            $this->entityManager->flush();
            $connection->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            // Entities flushed during the transaction are no longer in sync with the database
            $this->entityManager->clear();

            throw $exception;
        }
    }
}
