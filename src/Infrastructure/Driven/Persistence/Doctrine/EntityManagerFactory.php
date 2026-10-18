<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class EntityManagerFactory
{
    public static function create(string $databaseUrl, bool $devMode = false): EntityManagerInterface
    {
        $config = ORMSetup::createXMLMetadataConfiguration([__DIR__ . '/Mapping'], $devMode);
        $config->enableNativeLazyObjects(true);

        $connectionParams = new DsnParser(['postgresql' => 'pdo_pgsql'])->parse($databaseUrl);
        $connection = DriverManager::getConnection($connectionParams, $config);

        return new EntityManager($connection, $config);
    }
}
