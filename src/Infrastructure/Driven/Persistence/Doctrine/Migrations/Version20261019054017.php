<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019054017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the patients table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE patients (id UUID NOT NULL, owner_id UUID NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, gender VARCHAR(16) NOT NULL, birth_date DATE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_patients_owner_name ON patients (owner_id, last_name, first_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE patients');
    }
}
