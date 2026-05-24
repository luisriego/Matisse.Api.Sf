<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused syndic_allocation_rule from billing_policy_month_snapshot.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billing_policy_month_snapshot DROP COLUMN IF EXISTS syndic_allocation_rule');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE billing_policy_month_snapshot ADD syndic_allocation_rule VARCHAR(32) NOT NULL DEFAULT 'equal_parts'");
        $this->addSql('ALTER TABLE billing_policy_month_snapshot ALTER COLUMN syndic_allocation_rule DROP DEFAULT');
    }
}
