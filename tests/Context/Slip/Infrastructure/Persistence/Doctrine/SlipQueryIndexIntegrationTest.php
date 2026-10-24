<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Persistence\Doctrine;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversNothing]
final class SlipQueryIndexIntegrationTest extends KernelTestCase
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::$kernel->getContainer()->get('doctrine')->getConnection();
    }

    /** @group integration-indexes */
    public function test_expense_table_has_compound_index_on_due_date_and_is_active(): void
    {
        $this->assertPgIndexExists('expense', 'idx_expense_due_active');
    }

    /** @group integration-indexes */
    public function test_income_table_has_compound_index_on_due_date_and_is_active(): void
    {
        $this->assertPgIndexExists('income', 'idx_income_due_active');
    }

    /** @group integration-indexes */
    public function test_slip_table_has_compound_index_on_due_date_and_status(): void
    {
        $this->assertPgIndexExists('slip', 'idx_slip_due_status');
    }

    /** @group integration-indexes */
    public function test_indexes_are_btree(): void
    {
        $rows = $this->connection->executeQuery(
            "SELECT indexname, tablename, indexdef
             FROM pg_indexes
             WHERE schemaname = 'public'
               AND tablename IN ('expense', 'income', 'slip')
               AND indexname LIKE 'idx_%'
             ORDER BY tablename, indexname"
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            self::assertStringContainsString(
                'CREATE INDEX',
                $row['indexdef'],
                "Index {$row['tablename']}.{$row['indexname']} should be a standard B-Tree index"
            );
        }
    }

    private function assertPgIndexExists(string $table, string $indexName): void
    {
        $result = $this->connection->executeQuery(
            "SELECT 1 FROM pg_indexes WHERE schemaname = 'public' AND tablename = :table AND indexname = :index",
            ['table' => $table, 'index' => $indexName]
        )->fetchOne();

        self::assertTrue(
            (bool) $result,
            "Expected index {$indexName} on table {$table} not found in pg_indexes"
        );
    }
}
