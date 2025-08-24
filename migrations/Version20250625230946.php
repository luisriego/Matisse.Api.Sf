<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625230946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE event_store ALTER id TYPE CHAR(36)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_store.id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE event_store ALTER id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_store ALTER id TYPE UUID
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN event_store.id IS '(DC2Type:uuid)'
        SQL);
    }
}
