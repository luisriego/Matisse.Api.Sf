<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Service;

use App\Context\BankStatement\Application\Dto\ExpectedExpenseCreateOrUpdateDto;
use App\Context\BankStatement\Application\Dto\ExpectedExpensePreviewDto;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Forecast\Domain\Service\ExpectedExpenseFrequencyInferrer;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use DateMalformedStringException;
use DateTimeImmutable;

use function preg_replace;
use function trim;

final readonly class ExpectedExpensePreviewSuggester
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ExpectedExpenseFrequencyInferrer $frequencyInferrer,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function suggestForDebit(
        ?string $recurringExpenseId,
        string $memo,
        string $postedAt,
    ): ExpectedExpensePreviewDto {
        if ($recurringExpenseId !== null && $recurringExpenseId !== '') {
            return $this->fromExistingRecurring($recurringExpenseId);
        }

        $dueDay = (int) (new DateTimeImmutable($postedAt))->format('j');

        return new ExpectedExpensePreviewDto(
            null,
            new ExpectedExpenseCreateOrUpdateDto(
                displayName: $this->displayNameFromMemo($memo),
                frequency: 'monthly',
                amountKind: 'variable',
                dueDay: $dueDay,
            ),
        );
    }

    private function fromExistingRecurring(string $recurringExpenseId): ExpectedExpensePreviewDto
    {
        try {
            $recurring = $this->recurringExpenseRepository->findOneByIdOrFail($recurringExpenseId);
        } catch (ResourceNotFoundException) {
            return new ExpectedExpensePreviewDto($recurringExpenseId, null);
        }

        $frequency = $this->frequencyInferrer->infer($recurring->monthsOfYear());

        return new ExpectedExpensePreviewDto(
            $recurringExpenseId,
            new ExpectedExpenseCreateOrUpdateDto(
                displayName: trim((string) ($recurring->description() ?? '')),
                frequency: $frequency['frequency'],
                amountKind: $recurring->hasPredefinedAmount() ? 'fixed' : 'variable',
                monthsOfYear: $frequency['frequency'] === 'custom' ? $frequency['monthsOfYear'] : null,
                dueDay: $recurring->dueDay(),
            ),
        );
    }

    private function displayNameFromMemo(string $memo): string
    {
        $normalized = trim($memo);
        $normalized = preg_replace('/^DA\s+/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/^BOLETO\s+PAGO\s+/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+\d{5,}.*$/', '', $normalized) ?? $normalized;

        return trim($normalized) !== '' ? trim($normalized) : trim($memo);
    }
}
