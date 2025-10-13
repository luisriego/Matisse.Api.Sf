<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250928120341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Account
        $this->addSql('ALTER TABLE account ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN account.id IS \'(DC2Type:uuid)\'');

        // Event store
        $this->addSql('ALTER TABLE event_store ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE event_store ALTER aggregate_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN event_store.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN event_store.aggregate_id IS \'(DC2Type:uuid)\'');

        // Obsoletos (si existen)
        $this->addSql('DROP INDEX IF EXISTS idx_2d3a8da6b149c95e');
        $this->addSql('DROP INDEX IF EXISTS idx_2d3a8da6c54c8c93');

        // Expense
        $this->addSql('ALTER TABLE expense ADD expense_type_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE expense ADD recurring_expense_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE expense ADD resident_unit_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE expense DROP COLUMN type_id');
        $this->addSql('ALTER TABLE expense DROP COLUMN recurring_id');

        $this->addSql('ALTER TABLE expense ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE expense ALTER account_id TYPE UUID');
        $this->addSql('ALTER TABLE expense ALTER account_id DROP NOT NULL');
        $this->addSql('ALTER TABLE expense ALTER is_active DROP DEFAULT');

        $this->addSql('COMMENT ON COLUMN expense.expense_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.recurring_expense_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN expense.account_id IS \'(DC2Type:uuid)\'');

        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA69B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6A857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA64599D2DA FOREIGN KEY (recurring_expense_id) REFERENCES recurring_expense (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6A857C7A9 ON expense (expense_type_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA64599D2DA ON expense (recurring_expense_id)');

        // ExpenseType
        $this->addSql('ALTER TABLE expense_type ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN expense_type.id IS \'(DC2Type:uuid)\'');

        // Income
        $this->addSql('ALTER TABLE income ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE income ALTER COLUMN resident_unit_id DROP DEFAULT');
        $this->addSql('UPDATE income SET resident_unit_id = NULL WHERE resident_unit_id IS NOT NULL AND resident_unit_id !~* \'^[0-9a-f-]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$\'');
        $this->addSql('ALTER TABLE income ALTER COLUMN resident_unit_id TYPE UUID USING resident_unit_id::uuid');
        $this->addSql('ALTER TABLE income ALTER COLUMN type_id DROP DEFAULT');
        $this->addSql('UPDATE income SET type_id = NULL WHERE type_id IS NOT NULL AND type_id !~* \'^[0-9a-f-]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$\'');
        $this->addSql('ALTER TABLE income ALTER COLUMN type_id TYPE UUID USING type_id::uuid');
        $this->addSql('COMMENT ON COLUMN income.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN income.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN income.type_id IS \'(DC2Type:uuid)\'');

        // Sanea huérfanos antes de FK
        $this->addSql('UPDATE income i SET resident_unit_id = NULL WHERE resident_unit_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM resident_units ru WHERE ru.id = i.resident_unit_id)');

        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D05BCDE561 FOREIGN KEY (resident_unit_id) REFERENCES resident_units (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D0C54C8C93 FOREIGN KEY (type_id) REFERENCES income_type (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');

        // IncomeType
        $this->addSql('ALTER TABLE income_type ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN income_type.id IS \'(DC2Type:uuid)\'');

        // RecurringExpense
        $this->addSql('ALTER TABLE recurring_expense DROP COLUMN account_id');
        $this->addSql('ALTER TABLE recurring_expense ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE recurring_expense ALTER COLUMN expense_type_id DROP DEFAULT');
        $this->addSql('UPDATE recurring_expense SET expense_type_id = NULL WHERE expense_type_id IS NOT NULL AND expense_type_id !~* \'^[0-9a-f-]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$\'');
        $this->addSql('ALTER TABLE recurring_expense ALTER COLUMN expense_type_id TYPE UUID USING expense_type_id::uuid');
        $this->addSql('COMMENT ON COLUMN recurring_expense.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN recurring_expense.expense_type_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE recurring_expense ADD CONSTRAINT FK_F5CC182FA857C7A9 FOREIGN KEY (expense_type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // ResidentUnits (asegurar json no nulo)
        $this->addSql('ALTER TABLE resident_units ALTER id TYPE UUID');
        $this->addSql("ALTER TABLE resident_units ALTER COLUMN notification_recipients SET DEFAULT '[]'::json");
        $this->addSql("UPDATE resident_units SET notification_recipients = '[]'::json WHERE notification_recipients IS NULL");
        $this->addSql('ALTER TABLE resident_units ALTER notification_recipients SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN resident_units.id IS \'(DC2Type:uuid)\'');

        // Slip
        $this->addSql('ALTER TABLE slip ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE slip ALTER COLUMN resident_unit_id DROP DEFAULT');
        $this->addSql('UPDATE slip SET resident_unit_id = NULL WHERE resident_unit_id IS NOT NULL AND resident_unit_id !~* \'^[0-9a-f-]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$\'');
        $this->addSql('ALTER TABLE slip ALTER COLUMN resident_unit_id TYPE UUID USING resident_unit_id::uuid');
        // Sanea huérfanos antes de FK
        $this->addSql('UPDATE slip s SET resident_unit_id = NULL WHERE resident_unit_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM resident_units ru WHERE ru.id = s.resident_unit_id)');
        $this->addSql('COMMENT ON COLUMN slip.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN slip.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE slip ADD CONSTRAINT FK_FD3943F85BCDE561 FOREIGN KEY (resident_unit_id) REFERENCES resident_units (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Users: crear columna si no existe, luego castear/sanear/FK
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS resident_unit_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE users ALTER COLUMN resident_unit_id DROP DEFAULT');
        $this->addSql('UPDATE users SET resident_unit_id = NULL WHERE resident_unit_id IS NOT NULL AND resident_unit_id !~* \'^[0-9a-f-]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$\'');
        $this->addSql('ALTER TABLE users ALTER COLUMN resident_unit_id TYPE UUID USING resident_unit_id::uuid');
        // Sanea huérfanos antes de FK
        $this->addSql('UPDATE users u SET resident_unit_id = NULL WHERE resident_unit_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM resident_units ru WHERE ru.id = u.resident_unit_id)');
        $this->addSql('COMMENT ON COLUMN users.resident_unit_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN users.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E95BCDE561 FOREIGN KEY (resident_unit_id) REFERENCES resident_units (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_1483A5E95BCDE561 ON users (resident_unit_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');

        // Slip
        $this->addSql('ALTER TABLE slip DROP CONSTRAINT FK_FD3943F85BCDE561');
        $this->addSql('ALTER TABLE slip ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE slip ALTER resident_unit_id TYPE VARCHAR(36)');
        $this->addSql('COMMENT ON COLUMN slip.id IS NULL');
        $this->addSql('COMMENT ON COLUMN slip.resident_unit_id IS NULL');

        // Event store
        $this->addSql('ALTER TABLE event_store ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE event_store ALTER aggregate_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN event_store.id IS NULL');
        $this->addSql('COMMENT ON COLUMN event_store.aggregate_id IS NULL');

        // Income
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D05BCDE561');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D0C54C8C93');
        $this->addSql('ALTER TABLE income ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE income ALTER resident_unit_id TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE income ALTER type_id TYPE VARCHAR(36)');
        $this->addSql('COMMENT ON COLUMN income.id IS NULL');
        $this->addSql('COMMENT ON COLUMN income.resident_unit_id IS NULL');
        $this->addSql('COMMENT ON COLUMN income.type_id IS NULL');

        // IncomeType
        $this->addSql('ALTER TABLE income_type ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN income_type.id IS NULL');

        // ExpenseType
        $this->addSql('ALTER TABLE expense_type ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN expense_type.id IS NULL');

        // Users
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E95BCDE561');
        $this->addSql('DROP INDEX IF EXISTS IDX_1483A5E95BCDE561');
        $this->addSql('ALTER TABLE users ADD password_reset_token VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD password_reset_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE users DROP COLUMN resident_unit_id');
        $this->addSql('ALTER TABLE users ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN users.password_reset_requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.id IS NULL');

        // Expense
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA69B6B5FBA');
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA6A857C7A9');
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA64599D2DA');
        $this->addSql('DROP INDEX IDX_2D3A8DA6A857C7A9');
        $this->addSql('DROP INDEX IDX_2D3A8DA64599D2DA');
        $this->addSql('ALTER TABLE expense ADD type_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE expense ADD recurring_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE expense DROP expense_type_id');
        $this->addSql('ALTER TABLE expense DROP recurring_expense_id');
        $this->addSql('ALTER TABLE expense DROP resident_unit_id');
        $this->addSql('ALTER TABLE expense ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE expense ALTER account_id TYPE UUID');
        $this->addSql('ALTER TABLE expense ALTER account_id SET NOT NULL');
        $this->addSql('ALTER TABLE expense ALTER is_active SET DEFAULT true');
        $this->addSql('COMMENT ON COLUMN expense.id IS NULL');
        $this->addSql('COMMENT ON COLUMN expense.account_id IS NULL');
        $this->addSql('CREATE INDEX idx_2d3a8da6b149c95e ON expense (recurring_id)');
        $this->addSql('CREATE INDEX idx_2d3a8da6c54c8c93 ON expense (type_id)');

        // ResidentUnits
        $this->addSql('ALTER TABLE resident_units ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE resident_units ALTER notification_recipients DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN resident_units.id IS NULL');

        // Account
        $this->addSql('ALTER TABLE account ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN account.id IS NULL');

        // RecurringExpense
        $this->addSql('ALTER TABLE recurring_expense DROP CONSTRAINT FK_F5CC182FA857C7A9');
        $this->addSql('ALTER TABLE recurring_expense ADD account_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE recurring_expense ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE recurring_expense ALTER expense_type_id TYPE VARCHAR(36)');
        $this->addSql('COMMENT ON COLUMN recurring_expense.id IS NULL');
        $this->addSql('COMMENT ON COLUMN recurring_expense.expense_type_id IS NULL');
    }
}