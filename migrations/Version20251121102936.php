<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121102936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS condominium_configuration');
        $this->addSql('ALTER TABLE expense ADD attachment TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE recurring_expense ADD account_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE recurring_expense ADD has_predefined_amount BOOLEAN DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN recurring_expense.account_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE condominium_configuration (id UUID NOT NULL, reserve_fund_amount INT NOT NULL, construction_fund_amount INT NOT NULL, effective_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.effective_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE recurring_expense DROP account_id');
        $this->addSql('ALTER TABLE recurring_expense DROP has_predefined_amount');
        $this->addSql('ALTER TABLE expense DROP attachment');
    }
}
