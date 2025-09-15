<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912185926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP age');
        $this->addSql('ALTER TABLE users RENAME COLUMN token TO confirmation_token');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD age SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE users RENAME COLUMN confirmation_token TO token');
    }
}
