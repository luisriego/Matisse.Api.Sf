<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add period_closure table and origin column to slip for import/close features.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE period_closure (
            id UUID NOT NULL,
            year INT NOT NULL,
            month INT NOT NULL,
            closed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT UNIQ_PERIOD_CLOSURE_YM UNIQUE (year, month)
        )');
        $this->addSql("COMMENT ON COLUMN period_closure.id IS '(DC2Type:uuid)'");

        $this->addSql("ALTER TABLE slip ADD origin VARCHAR(20) NOT NULL DEFAULT 'generated'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE slip DROP COLUMN origin');
        $this->addSql('DROP TABLE period_closure');
    }
}
