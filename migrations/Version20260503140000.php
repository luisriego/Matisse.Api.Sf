<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Slip generation fee snapshot for OFX settlement split; income.account_id for ledger queries.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE slip_generation_parameter_snapshot (
            id UUID NOT NULL,
            expense_year INT NOT NULL,
            expense_month INT NOT NULL,
            extra_fee_per_unit_cents INT NOT NULL,
            reserve_fund_per_unit_cents INT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT UNIQ_SLIP_GEN_SNAPSHOT_YM UNIQUE (expense_year, expense_month)
        )');
        $this->addSql("COMMENT ON COLUMN slip_generation_parameter_snapshot.id IS '(DC2Type:uuid)'");

        $this->addSql('ALTER TABLE income ADD account_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_INCOME_ACCOUNT FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql("COMMENT ON COLUMN income.account_id IS '(DC2Type:uuid)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_INCOME_ACCOUNT');
        $this->addSql('ALTER TABLE income DROP COLUMN account_id');
        $this->addSql('DROP TABLE slip_generation_parameter_snapshot');
    }
}
