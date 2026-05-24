<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406235250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bank_transaction_import (id UUID NOT NULL, fit_id VARCHAR(64) NOT NULL, bank_account_id VARCHAR(64) NOT NULL, expense_id UUID NOT NULL, imported_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uq_bank_transaction_import_fitid_account ON bank_transaction_import (fit_id, bank_account_id)');
        $this->addSql('COMMENT ON COLUMN bank_transaction_import.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN bank_transaction_import.expense_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN bank_transaction_import.imported_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE bank_transaction_import');
    }
}
