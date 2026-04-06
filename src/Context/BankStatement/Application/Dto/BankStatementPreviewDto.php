<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

/**
 * Full preview of an OFX import: parsed metadata + per-transaction reviews.
 */
final readonly class BankStatementPreviewDto
{
    /**
     * @param TransactionPreviewDto[] $expenses  DEBIT lines to classify as condominium expenses
     * @param TransactionPreviewDto[] $credits   CREDIT lines for income reconciliation
     */
    public function __construct(
        public readonly string $bankId,
        public readonly string $accountId,
        public readonly string $currency,
        /** ISO date Y-m-d */
        public readonly string $periodStart,
        /** ISO date Y-m-d */
        public readonly string $periodEnd,
        public readonly ?int $ledgerBalanceInCents,
        public readonly ?string $ledgerBalanceDate,
        public readonly array $expenses,
        public readonly array $credits,
        public readonly int $totalNeedsReview,
        public readonly int $totalPreFilled,
    ) {}
}
