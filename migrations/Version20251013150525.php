<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013150525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE condominium_configuration');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT fk_3fa862d09b6b5fba');
        $this->addSql('DROP INDEX idx_3fa862d09b6b5fba');
        $this->addSql('ALTER TABLE income DROP account_id');
        $this->addSql('ALTER TABLE recurring_expense ADD has_predefined_amount BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE condominium_configuration (id UUID NOT NULL, reserve_fund_amount INT NOT NULL, construction_fund_amount INT NOT NULL, effective_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.effective_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE recurring_expense DROP has_predefined_amount');
        $this->addSql('ALTER TABLE income ADD account_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN income.account_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT fk_3fa862d09b6b5fba FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_3fa862d09b6b5fba ON income (account_id)');
    }
}
