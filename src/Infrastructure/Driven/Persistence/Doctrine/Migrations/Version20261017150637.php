<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017150637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the accounts table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accounts (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, registered_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_accounts_email ON accounts (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE accounts');
    }
}
