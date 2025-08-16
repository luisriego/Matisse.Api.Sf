<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250816203502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status and paid_at to slip';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE slip ADD status VARCHAR(20) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE slip ADD paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN slip.paid_at IS '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
        $this->addSql(<<<'SQL'
            ALTER TABLE slip DROP status
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE slip DROP paid_at
        SQL);
    }
}
