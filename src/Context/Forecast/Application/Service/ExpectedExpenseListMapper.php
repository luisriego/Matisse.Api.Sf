<?php

declare(strict_types=1);

namespace App\Context\Forecast\Application\Service;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Forecast\Domain\Service\ExpectedExpenseFrequencyInferrer;

final readonly class ExpectedExpenseListMapper
{
    public function __construct(
        private ExpectedExpenseFrequencyInferrer $frequencyInferrer,
        private ExpenseRepository $expenseRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function map(RecurringExpense $recurring): array
    {
        $type = $recurring->type();
        $frequency = $this->frequencyInferrer->infer($recurring->monthsOfYear());

        $row = [
            'id' => $recurring->id(),
            'displayName' => $recurring->description() ?? $type->name() ?? '',
            'expenseTypeId' => $type->id(),
            'expenseTypeCode' => $type->code(),
            'frequency' => $frequency['frequency'],
            'monthsOfYear' => $frequency['monthsOfYear'],
            'amountKind' => $recurring->hasPredefinedAmount() ? 'fixed' : 'variable',
            'lastAmountCents' => $recurring->amount(),
            'dueDay' => $recurring->dueDay(),
            'isActive' => $recurring->isActive(),
        ];

        $lastReconciledMonth = $this->expenseRepository->findLatestDueDateMonthByRecurringExpenseId($recurring->id());
        if ($lastReconciledMonth !== null) {
            $row['lastReconciledMonth'] = $lastReconciledMonth;
        }

        return $row;
    }
}
