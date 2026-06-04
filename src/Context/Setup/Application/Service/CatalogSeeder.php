<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\Service;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeCode;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDistributionMethod;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeName;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\Income\Domain\ValueObject\IncomeTypeCode;
use App\Context\Income\Domain\ValueObject\IncomeTypeName;
use App\Shared\Domain\Catalog\ExpenseTypeCatalog;
use App\Shared\Domain\Catalog\IncomeTypeCatalog;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Seeds the reference catalogs (expense and income types) when they are empty.
 * Idempotent: never truncates and skips contexts that already have data, so it is
 * safe to call on every setup entry without touching existing records.
 */
final readonly class CatalogSeeder
{
    public function __construct(
        private ExpenseTypeRepository $expenseTypeRepository,
        private IncomeTypeRepository $incomeTypeRepository,
    ) {}

    public function ensureSeeded(): void
    {
        $this->ensureExpenseTypes();
        $this->ensureIncomeTypes();
    }

    private function ensureExpenseTypes(): void
    {
        if ([] !== $this->expenseTypeRepository->findAll()) {
            return;
        }

        foreach (ExpenseTypeCatalog::TYPES as $code => $data) {
            $expenseType = ExpenseType::create(
                new ExpenseTypeId((string) Uuid::random()),
                new ExpenseTypeCode($code),
                new ExpenseTypeName($data['name']),
                new ExpenseTypeDistributionMethod($data['distributionMethod']),
                new ExpenseTypeDescription($data['description']),
            );

            $this->expenseTypeRepository->save($expenseType);
        }
    }

    private function ensureIncomeTypes(): void
    {
        if ([] !== $this->incomeTypeRepository->findAll()) {
            return;
        }

        foreach (IncomeTypeCatalog::TYPES as $code => $data) {
            $incomeType = IncomeType::create(
                IncomeId::random(),
                new IncomeTypeName($data['name']),
                new IncomeTypeCode($code),
                $data['description'],
            );

            $this->incomeTypeRepository->save($incomeType, true);
        }
    }
}
