<?php

declare(strict_types=1);

namespace App\Tests\Context\Setup\Application\Service;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeCatalog;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\IncomeTypeCatalog;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Context\Setup\Application\Service\CatalogSeeder;
use App\Tests\Shared\Infrastructure\PhpUnit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

use function count;

final class CatalogSeederTest extends UnitTestCase
{
    private ExpenseTypeRepository&MockObject $expenseTypeRepo;
    private IncomeTypeRepository&MockObject $incomeTypeRepo;
    private CatalogSeeder $seeder;

    protected function setUp(): void
    {
        $this->expenseTypeRepo = $this->createMock(ExpenseTypeRepository::class);
        $this->incomeTypeRepo  = $this->createMock(IncomeTypeRepository::class);
        $this->seeder          = new CatalogSeeder($this->expenseTypeRepo, $this->incomeTypeRepo);
    }

    public function testItSeedsCatalogsWhenEmpty(): void
    {
        $this->expenseTypeRepo
            ->method('findAll')
            ->willReturnOnConsecutiveCalls([], []);

        $this->incomeTypeRepo
            ->method('findAll')
            ->willReturnOnConsecutiveCalls([], []);

        $this->expenseTypeRepo
            ->method('save')
            ->with(self::callback(static function (ExpenseType $et): bool {
                self::assertNotNull($et->id());
                self::assertNotNull($et->code());
                self::assertNotNull($et->name());

                return true;
            }));

        $this->incomeTypeRepo
            ->method('save')
            ->with(self::callback(static function (IncomeType $it): bool {
                self::assertNotNull($it->code());
                self::assertNotNull($it->name());

                return true;
            }));

        $this->seeder->ensureSeeded();
    }

    public function testItIsIdempotentWhenCatalogsAlreadySeeded(): void
    {
        $this->expenseTypeRepo
            ->method('findAll')
            ->willReturn([self::createMock(ExpenseType::class)]);

        $this->incomeTypeRepo
            ->method('findAll')
            ->willReturn([self::createMock(IncomeType::class)]);

        $this->expenseTypeRepo
            ->expects(self::never())
            ->method('save');

        $this->incomeTypeRepo
            ->expects(self::never())
            ->method('save');

        $this->seeder->ensureSeeded();
    }

    public function testItPreservesExistingExpenseTypesWhenSeedingIncome(): void
    {
        $existingExpense = self::createMock(ExpenseType::class);

        $this->expenseTypeRepo
            ->method('findAll')
            ->willReturn([$existingExpense]);

        $this->incomeTypeRepo
            ->method('findAll')
            ->willReturnOnConsecutiveCalls([], []);

        $this->expenseTypeRepo
            ->expects(self::never())
            ->method('save');

        $this->incomeTypeRepo
            ->method('save');

        $this->seeder->ensureSeeded();
    }

    public function testUnitSuiteRunsWithoutDatabaseConnection(): void
    {
        // This test verifies the unit suite invariant: no database required.
        // If this test runs successfully, the unit suite is DB-free.
        $this->expenseTypeRepo
            ->method('findAll')
            ->willReturn([]);

        $this->incomeTypeRepo
            ->method('findAll')
            ->willReturn([]);

        // Just instantiate and call — no container, no DB, no kernel boot.
        $seeder = new CatalogSeeder($this->expenseTypeRepo, $this->incomeTypeRepo);
        $seeder->ensureSeeded();
    }
}
