<?php

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Modified
 * Updated to create missing UserAccount table & columns
 */
class Version20150520103846 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'CREATE TABLE UserAccount (
            id SERIAL NOT NULL,
            isAdmin BOOLEAN NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) DEFAULT NULL,
            created TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            updated TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            status VARCHAR(255) NOT NULL DEFAULT \'active\',
            oneTimePasswordSetToken VARCHAR(255) DEFAULT NULL,
            oneTimePasswordSetTokenGeneratedTime TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id))'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A8761C51E7927C74 ON UserAccount (email)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql('DROP TABLE UserAccount');
    }
}
