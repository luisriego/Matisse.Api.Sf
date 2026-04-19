<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Dto;

use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConfirmLine',
    required: ['fitId', 'amountInCents', 'postedAt', 'memo', 'accountId', 'dueDate'],
    properties: [
        new OA\Property(property: 'fitId',              type: 'string',  example: 'FIT-20260310-001'),
        new OA\Property(property: 'amountInCents',      type: 'integer', example: 15000),
        new OA\Property(property: 'postedAt',           type: 'string',  format: 'date', example: '2026-03-10'),
        new OA\Property(property: 'memo',               type: 'string',  example: 'COPASA AGUA'),
        new OA\Property(property: 'lineType',           type: 'string',  enum: ['expense', 'income'], example: 'expense',
            description: 'Defaults to "expense" when omitted (back-compat with DEBIT-only confirm).'),
        new OA\Property(property: 'expenseTypeId',      type: 'string',  format: 'uuid', nullable: true,
            description: 'Required for expense lines; ignored for income lines.'),
        new OA\Property(property: 'incomeTypeId',       type: 'string',  format: 'uuid', nullable: true,
            description: 'Used for income lines. Falls back to DEFAULT_BANK_CREDIT_INCOME_TYPE_ID env if null.'),
        new OA\Property(property: 'accountId',          type: 'string',  format: 'uuid'),
        new OA\Property(property: 'dueDate',            type: 'string',  format: 'date', example: '2026-03-10'),
        new OA\Property(property: 'description',        type: 'string',  nullable: true),
        new OA\Property(property: 'recurringExpenseId', type: 'string',  format: 'uuid', nullable: true),
        new OA\Property(property: 'residentUnitId',     type: 'string',  format: 'uuid', nullable: true,
            description: 'Optional for both; required by most expense flows, usually null for bank CREDIT lines.'),
        new OA\Property(property: 'creditKind', type: 'string', enum: ['boleto_settlement', 'other'],
            example: 'boleto_settlement',
            description: 'Only applies to income lines. "boleto_settlement" lines are consolidated into a single monthly income and validated against the previous-month total; "other" lines (bank interest, refunds, etc.) become individual incomes with no validation.'),
    ],
)]
final readonly class ConfirmLineRequestDto
{
    public function __construct(
        public readonly string  $fitId,
        public readonly int     $amountInCents,
        public readonly string  $postedAt,
        public readonly string  $memo,
        public readonly string  $accountId,
        public readonly string  $dueDate,
        public readonly string  $lineType           = ConfirmLineDto::TYPE_EXPENSE,
        public readonly ?string $expenseTypeId      = null,
        public readonly ?string $incomeTypeId       = null,
        public readonly ?string $description        = null,
        public readonly ?string $recurringExpenseId = null,
        public readonly ?string $residentUnitId     = null,
        public readonly string  $creditKind         = ConfirmLineDto::CREDIT_KIND_BOLETO_SETTLEMENT,
    ) {}
}
