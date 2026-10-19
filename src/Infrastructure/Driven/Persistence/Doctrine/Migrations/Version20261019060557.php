<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driven\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019060557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the rooms table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rooms (id UUID NOT NULL, owner_id UUID NOT NULL, name VARCHAR(20) NOT NULL, capacity SMALLINT NOT NULL, occupant_ids JSONB NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_rooms_owner_name ON rooms (owner_id, name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rooms');
    }
}
