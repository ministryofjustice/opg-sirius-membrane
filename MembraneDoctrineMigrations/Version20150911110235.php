<?php

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150911110235 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // This is a one way change, it cannot be undone
        $this->addSql('UPDATE useraccount SET email = LOWER(email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
    }
}
