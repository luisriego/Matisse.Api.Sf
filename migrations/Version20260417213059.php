<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417213059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add income_id column to bank_transaction_import and make expense_id nullable (supports OFX credit imports).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_transaction_import ADD income_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE bank_transaction_import ALTER expense_id DROP NOT NULL');
        $this->addSql("COMMENT ON COLUMN bank_transaction_import.income_id IS '(DC2Type:uuid)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE bank_transaction_import SET expense_id = income_id WHERE expense_id IS NULL AND income_id IS NOT NULL');
        $this->addSql('ALTER TABLE bank_transaction_import ALTER expense_id SET NOT NULL');
        $this->addSql('ALTER TABLE bank_transaction_import DROP income_id');
    }
}
