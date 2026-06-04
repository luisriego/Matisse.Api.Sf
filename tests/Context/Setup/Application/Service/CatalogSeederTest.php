<?php

declare(strict_types=1);

namespace App\Tests\Context\Setup\Application\Service;

use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Context\Setup\Application\Service\CatalogSeeder;
use App\Shared\Domain\Catalog\ExpenseTypeCatalog;
use App\Shared\Domain\Catalog\IncomeTypeCatalog;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;

use function count;

final class CatalogSeederTest extends ApiTestCase
{
    public function testItSeedsCatalogsWhenEmptyAndIsIdempotent(): void
    {
        $container   = static::getContainer();
        $seeder      = $container->get(CatalogSeeder::class);
        $expenseRepo = $container->get(ExpenseTypeRepository::class);
        $incomeRepo  = $container->get(IncomeTypeRepository::class);

        self::assertSame([], $expenseRepo->findAll());
        self::assertSame([], $incomeRepo->findAll());

        $seeder->ensureSeeded();

        self::assertCount(count(ExpenseTypeCatalog::TYPES), $expenseRepo->findAll());
        self::assertCount(count(IncomeTypeCatalog::TYPES), $incomeRepo->findAll());

        // Second call must not duplicate anything.
        $seeder->ensureSeeded();

        self::assertCount(count(ExpenseTypeCatalog::TYPES), $expenseRepo->findAll());
        self::assertCount(count(IncomeTypeCatalog::TYPES), $incomeRepo->findAll());
    }
}
