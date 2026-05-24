<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove account.code; accounts are identified by UUID only.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account DROP COLUMN IF EXISTS code');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD code VARCHAR(16) DEFAULT NULL');
    }
}
