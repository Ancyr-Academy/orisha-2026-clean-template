<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driven\Persistence\Doctrine;

use Acme\Infrastructure\Driven\Persistence\Doctrine\EntityManagerFactory;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

/**
 * Runs against a real PostgreSQL database (see compose.yaml), recreating the schema before each test.
 * Tests are skipped when the database is not reachable.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::createEntityManager();

        try {
            $schemaTool = new SchemaTool($this->entityManager);
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        } catch (ConnectionException $exception) {
            self::markTestSkipped('Test database is not reachable: ' . $exception->getMessage());
        }
    }

    /**
     * Each entity manager opens its own connection, e.g. to act as a concurrent request.
     */
    protected static function createEntityManager(): EntityManagerInterface
    {
        return EntityManagerFactory::create(
            getenv('TEST_DATABASE_URL') ?: 'postgresql://acme:acme@127.0.0.1:5433/acme_test?serverVersion=18&charset=utf8',
            devMode: true,
        );
    }
}
