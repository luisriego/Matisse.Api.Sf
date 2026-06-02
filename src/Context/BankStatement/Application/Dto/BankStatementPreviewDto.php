<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use OpenApi\Attributes as OA;

/**
 * Full preview of an OFX import: parsed metadata + per-transaction reviews.
 */
#[OA\Schema(
    schema: 'BankStatementPreview',
    properties: [
        new OA\Property(property: 'bankId', type: 'string', example: '0260'),
        new OA\Property(property: 'accountId', type: 'string', example: '12345-6'),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
        new OA\Property(property: 'periodStart', type: 'string', format: 'date', example: '2026-03-01'),
        new OA\Property(property: 'periodEnd', type: 'string', format: 'date', example: '2026-03-31'),
        new OA\Property(property: 'ledgerBalanceInCents', type: 'integer', nullable: true, example: 250000),
        new OA\Property(property: 'ledgerBalanceDate', type: 'string', format: 'date', nullable: true),
        new OA\Property(
            property: 'expenses',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TransactionPreview'),
            description: 'DEBIT lines to classify as condominium expenses',
        ),
        new OA\Property(
            property: 'credits',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TransactionPreview'),
            description: 'CREDIT lines for income reconciliation',
        ),
        new OA\Property(property: 'totalNeedsReview', type: 'integer', example: 5),
        new OA\Property(property: 'totalPreFilled', type: 'integer', example: 12),
    ],
)]
final readonly class BankStatementPreviewDto
{
    /**
     * @param TransactionPreviewDto[] $expenses DEBIT lines to classify as condominium expenses
     * @param TransactionPreviewDto[] $credits  CREDIT lines for income reconciliation
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
