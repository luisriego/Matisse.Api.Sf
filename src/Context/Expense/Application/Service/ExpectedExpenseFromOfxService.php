<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\Service;

use App\Context\BankStatement\Application\Dto\ExpectedExpenseCreateOrUpdateDto;
use App\Context\BankStatement\Application\Dto\ExpectedExpenseSpecDto;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;
use InvalidArgumentException;

use function sprintf;
use function trim;

/**
 * Creates or updates RecurringExpense memory when reconciling OFX (option B).
 * Does not create the reconciled Expense — the caller uses EnterMonthlyRecurringExpenseCommand.
 */
final readonly class ExpectedExpenseFromOfxService
{
    private const END_DATE = '2099-12-31';

    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ExpenseTypeRepository $expenseTypeRepository,
        private ExpectedExpenseFrequencyMapper $frequencyMapper,
    ) {}

    /**
     * @return array{recurringExpenseId: string, created: bool, linked: bool}
     *
     * @throws DateMalformedStringException
     */
    public function upsertFromOfxLine(
        bool $isExpectedExpense,
        ?ExpectedExpenseSpecDto $spec,
        ?string $legacyRecurringExpenseId,
        string $expenseTypeId,
        string $accountId,
        int $amountInCents,
        string $dueDate,
        string $memo,
        ?string $description,
    ): array {
        if (!$isExpectedExpense) {
            throw new InvalidArgumentException('upsertFromOfxLine called when isExpectedExpense is false.');
        }

        $recurringId = $spec?->recurringExpenseId ?? $legacyRecurringExpenseId;
        $createOrUpdate = $spec?->createOrUpdate;

        if ($recurringId !== null && $recurringId !== '') {
            $recurring = $this->recurringExpenseRepository->findOneByIdOrFail($recurringId);
            $this->applyCreateOrUpdateToExisting($recurring, $createOrUpdate, $amountInCents, $dueDate);
            $this->recurringExpenseRepository->save($recurring, true);

            return [
                'recurringExpenseId' => $recurring->id(),
                'created' => false,
                'linked' => true,
            ];
        }

        if ($createOrUpdate === null) {
            throw new InvalidArgumentException(
                'Expected expense lines require recurringExpenseId or expectedExpense.createOrUpdate.',
            );
        }

        $recurring = $this->createRecurring(
            $createOrUpdate,
            $expenseTypeId,
            $accountId,
            $amountInCents,
            $dueDate,
            $memo,
            $description,
        );
        $this->recurringExpenseRepository->save($recurring, true);

        return [
            'recurringExpenseId' => $recurring->id(),
            'created' => true,
            'linked' => true,
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    private function createRecurring(
        ExpectedExpenseCreateOrUpdateDto $spec,
        string $expenseTypeId,
        string $accountId,
        int $amountInCents,
        string $dueDate,
        string $memo,
        ?string $description,
    ): RecurringExpense {
        $type = $this->expenseTypeRepository->findOneByIdOrFail($expenseTypeId);
        $dueDay = $spec->dueDay ?? (int) (new DateTimeImmutable($dueDate))->format('j');
        $months = $this->frequencyMapper->monthsOfYear($spec->frequency, $spec->monthsOfYear);
        $hasPredefined = $spec->amountKind === 'fixed';
        $label = trim($spec->displayName) !== '' ? trim($spec->displayName) : ($description ?? $memo);

        return RecurringExpense::create(
            new ExpenseId(Uuid::random()->value()),
            $accountId,
            new ExpenseAmount($amountInCents),
            $type,
            new ExpenseDueDay($dueDay),
            $months,
            ExpenseStartDate::from($dueDate),
            ExpenseEndDate::from(self::END_DATE),
            $label,
            null,
            $hasPredefined,
        );
    }

    private function applyCreateOrUpdateToExisting(
        RecurringExpense $recurring,
        ?ExpectedExpenseCreateOrUpdateDto $spec,
        int $amountInCents,
        string $dueDate,
    ): void {
        $recurring->updateAmount($amountInCents);

        if ($spec === null) {
            return;
        }

        if (trim($spec->displayName) !== '') {
            $recurring->updateDescription(trim($spec->displayName));
        }

        $dueDay = $spec->dueDay ?? (int) (new DateTimeImmutable($dueDate))->format('j');
        $recurring->updateDueDay($dueDay);
        $recurring->updateMonthsOfYear(
            $this->frequencyMapper->monthsOfYear($spec->frequency, $spec->monthsOfYear),
        );
    }
}
