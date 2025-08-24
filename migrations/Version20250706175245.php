<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250706175245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isActive property to `Expense` entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE expense ADD is_active BOOLEAN NOT NULL DEFAULT true
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE expense DROP is_active
        SQL);
    }
}
