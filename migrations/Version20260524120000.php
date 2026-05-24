<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add billing_policy_month_snapshot table for monthly slip parameters.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE billing_policy_month_snapshot (
            target_month VARCHAR(7) NOT NULL,
            extra_fee_per_unit_cents INT NOT NULL,
            reserve_fund_per_unit_cents INT NOT NULL,
            syndic_share_total_cents INT NOT NULL,
            gas_price_per_m3_cents INT DEFAULT NULL,
            recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(target_month)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE billing_policy_month_snapshot');
    }
}
