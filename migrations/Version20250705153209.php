<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250705153209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1) Crear tabla recurring_expense
        $this->addSql(<<<'SQL'
            CREATE TABLE recurring_expense (
                id VARCHAR(255) NOT NULL,
                expense_type_id VARCHAR(36) NOT NULL,
                amount INT NOT NULL,
                description TEXT DEFAULT NULL,
                due_day INT NOT NULL,
                months_of_year JSON DEFAULT NULL,
                start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                is_active BOOLEAN NOT NULL,
                notes TEXT DEFAULT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        // 2) Índice y comentario en recurring_expense
        $this->addSql('CREATE INDEX IDX_F5CC182FA857C7A9 ON recurring_expense (expense_type_id)');
        $this->addSql("COMMENT ON COLUMN recurring_expense.created_at IS '(DC2Type:datetime_immutable)'");

        // 3) Añadir columnas faltantes en expense
        $this->addSql("ALTER TABLE expense ADD COLUMN type_id CHAR(36) DEFAULT NULL");
        $this->addSql("ALTER TABLE expense ADD COLUMN recurring_id VARCHAR(255) DEFAULT NULL");

        // 4) Claves foráneas en expense
        $this->addSql(<<<'SQL'
            ALTER TABLE expense
              ADD CONSTRAINT FK_2D3A8DA6C54C8C93
              FOREIGN KEY (type_id)
              REFERENCES expense_type (id)
              ON DELETE RESTRICT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE expense
              ADD CONSTRAINT FK_2D3A8DA6B149C95E
              FOREIGN KEY (recurring_id)
              REFERENCES recurring_expense (id)
              ON DELETE SET NULL
        SQL);

        // 5) Índices en expense para las nuevas columnas
        $this->addSql('CREATE INDEX IDX_2D3A8DA6C54C8C93 ON expense (type_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6B149C95E ON expense (recurring_id)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA6B149C95E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recurring_expense DROP CONSTRAINT FK_F5CC182FA857C7A9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE recurring_expense
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2D3A8DA6C54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2D3A8DA6B149C95E
        SQL);
    }
}
