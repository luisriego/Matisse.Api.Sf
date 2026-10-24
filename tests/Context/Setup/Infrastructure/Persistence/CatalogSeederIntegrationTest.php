<?php

declare(strict_types=1);

namespace App\Tests\Context\Setup\Infrastructure\Persistence;

use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Shared\Domain\Catalog\ExpenseTypeCatalog;
use App\Shared\Domain\Catalog\IncomeTypeCatalog;
use App\Context\Setup\Application\Service\CatalogSeeder;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function count;

final class CatalogSeederIntegrationTest extends ApiTestCase
{
    private ContainerInterface $container;
    private CatalogSeeder $seeder;
    private ExpenseTypeRepository $expenseRepo;
    private IncomeTypeRepository $incomeRepo;

    protected function setUp(): void
    {
        $this->container = self::getContainer();
        $this->seeder    = $this->container->get(CatalogSeeder::class);
        $this->expenseRepo = $this->container->get(ExpenseTypeRepository::class);
        $this->incomeRepo  = $this->container->get(IncomeTypeRepository::class);
    }

    public function testItSeedsCatalogsWhenEmptyAndIsIdempotent(): void
    {
        self::assertSame([], $this->expenseRepo->findAll());
        self::assertSame([], $this->incomeRepo->findAll());

        $this->seeder->ensureSeeded();

        self::assertCount(count(ExpenseTypeCatalog::TYPES), $this->expenseRepo->findAll());
        self::assertCount(count(IncomeTypeCatalog::TYPES), $this->incomeRepo->findAll());

        // Second call must not duplicate anything.
        $this->seeder->ensureSeeded();

        self::assertCount(count(ExpenseTypeCatalog::TYPES), $this->expenseRepo->findAll());
        self::assertCount(count(IncomeTypeCatalog::TYPES), $this->incomeRepo->findAll());
    }
}
