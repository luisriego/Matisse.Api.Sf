<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124161437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense_type ADD account_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN expense_type.account_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE expense_type ADD CONSTRAINT FK_3879194B9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3879194B9B6B5FBA ON expense_type (account_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE expense_type DROP CONSTRAINT FK_3879194B9B6B5FBA');
        $this->addSql('DROP INDEX IDX_3879194B9B6B5FBA');
        $this->addSql('ALTER TABLE expense_type DROP account_id');
    }
}
