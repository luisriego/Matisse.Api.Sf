<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407004929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable pgvector extension and create expense_embedding table with HNSW index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');

        $this->addSql(<<<SQL
            CREATE TABLE expense_embedding (
                id              UUID        NOT NULL,
                expense_id      UUID        NOT NULL,
                vector          vector(768) NOT NULL,
                description     TEXT        NOT NULL,
                embedding_model VARCHAR(100) NOT NULL,
                indexed_at      TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uq_expense_embedding_expense_id ON expense_embedding (expense_id)');
        $this->addSql('CREATE INDEX idx_expense_embedding_hnsw ON expense_embedding USING hnsw (vector vector_cosine_ops)');

        $this->addSql("COMMENT ON COLUMN expense_embedding.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN expense_embedding.expense_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN expense_embedding.indexed_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE expense_embedding');
        $this->addSql('DROP EXTENSION IF EXISTS vector');
    }
}
