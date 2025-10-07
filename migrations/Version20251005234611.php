<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005234611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account (id UUID NOT NULL, code VARCHAR(16) NOT NULL, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7D3656A477153098 ON account (code)');
        $this->addSql('COMMENT ON COLUMN account.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN account.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE condominium_configuration (id UUID NOT NULL, reserve_fund_amount INT NOT NULL, construction_fund_amount INT NOT NULL, effective_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.effective_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN condominium_configuration.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE event_store (id UUID NOT NULL, aggregate_id UUID NOT NULL, event_type VARCHAR(255) NOT NULL, payload JSON NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN event_store.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN event_store.aggregate_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN event_store.occurred_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE expense (id UUID NOT NULL, account_id UUID DEFAULT NULL, expense_type_id UUID DEFAULT NULL, recurring_expense_id UUID DEFAULT NULL, amount INT NOT NULL, description TEXT DEFAULT NULL, due_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN NOT NULL, resident_unit_id UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2D3A8DA69B6B5FBA ON expense (account_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6A857C7A9 ON expense (expense_type_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA64599D2DA ON expense (recurring_expense_id)');
        $this->addSql('COMMENT ON COLUMN expense.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.account_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.expense_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.recurring_expense_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.paid_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN expense.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN expense.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE expense_type (id UUID NOT NULL, code VARCHAR(10) NOT NULL, name VARCHAR(100) NOT NULL, distribution_method VARCHAR(10) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN expense_type.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE income (id UUID NOT NULL, resident_unit_id UUID DEFAULT NULL, type_id UUID DEFAULT NULL, amount INT NOT NULL, due_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3FA862D05BCDE561 ON income (resident_unit_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D0C54C8C93 ON income (type_id)');
        $this->addSql('COMMENT ON COLUMN income.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN income.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN income.type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN income.paid_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN income.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE income_type (id UUID NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN income_type.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE recurring_expense (id UUID NOT NULL, expense_type_id UUID NOT NULL, amount INT NOT NULL, description TEXT DEFAULT NULL, due_day INT NOT NULL, months_of_year JSON DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN NOT NULL, notes TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F5CC182FA857C7A9 ON recurring_expense (expense_type_id)');
        $this->addSql('COMMENT ON COLUMN recurring_expense.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN recurring_expense.expense_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN recurring_expense.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE resident_units (id UUID NOT NULL, unit VARCHAR(10) NOT NULL, ideal_fraction DOUBLE PRECISION NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, notification_recipients JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN resident_units.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resident_units.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE slip (id UUID NOT NULL, resident_unit_id UUID DEFAULT NULL, amount INT NOT NULL, status VARCHAR(20) NOT NULL, due_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FD3943F85BCDE561 ON slip (resident_unit_id)');
        $this->addSql('COMMENT ON COLUMN slip.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN slip.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN slip.due_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN slip.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN slip.paid_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, resident_unit_id UUID DEFAULT NULL, name VARCHAR(80) DEFAULT NULL, last_name VARCHAR(80) DEFAULT NULL, email VARCHAR(180) NOT NULL, gender VARCHAR(1) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, roles JSON NOT NULL, confirmation_token VARCHAR(40) DEFAULT NULL, password VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, password_reset_token VARCHAR(40) DEFAULT NULL, password_reset_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E95BCDE561 ON users (resident_unit_id)');
        $this->addSql('COMMENT ON COLUMN users.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN users.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN users.password_reset_requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA69B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6A857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA64599D2DA FOREIGN KEY (recurring_expense_id) REFERENCES recurring_expense (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D05BCDE561 FOREIGN KEY (resident_unit_id) REFERENCES resident_units (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D0C54C8C93 FOREIGN KEY (type_id) REFERENCES income_type (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE recurring_expense ADD CONSTRAINT FK_F5CC182FA857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE slip ADD CONSTRAINT FK_FD3943F85BCDE561 FOREIGN KEY (resident_unit_id) REFERENCES resident_units (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E95BCDE561 FOREIGN KEY (resident_unit_id) REFERENCES resident_units (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA69B6B5FBA');
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA6A857C7A9');
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA64599D2DA');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D05BCDE561');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D0C54C8C93');
        $this->addSql('ALTER TABLE recurring_expense DROP CONSTRAINT FK_F5CC182FA857C7A9');
        $this->addSql('ALTER TABLE slip DROP CONSTRAINT FK_FD3943F85BCDE561');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E95BCDE561');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE condominium_configuration');
        $this->addSql('DROP TABLE event_store');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('DROP TABLE income');
        $this->addSql('DROP TABLE income_type');
        $this->addSql('DROP TABLE recurring_expense');
        $this->addSql('DROP TABLE resident_units');
        $this->addSql('DROP TABLE slip');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
