<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use OpenApi\Attributes as OA;

/**
 * Represents how a bank transaction was previously categorised in a past month.
 * Returned inside each TransactionPreviewDto to assist the user in repeating the same assignment.
 */
#[OA\Schema(
    schema: 'PastAssignment',
    properties: [
        new OA\Property(property: 'month', type: 'integer', example: 3),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'amountInCents', type: 'integer', example: 15000),
        new OA\Property(property: 'expenseTypeId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'expenseTypeName', type: 'string', example: 'Água', nullable: true),
        new OA\Property(property: 'recurringExpenseId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'recurringExpenseName', type: 'string', nullable: true),
        new OA\Property(property: 'accountId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'confidence', type: 'number', format: 'float', example: 0.9),
    ],
)]
final readonly class PastAssignmentDto
{
    public function __construct(
        public readonly int $month,
        public readonly int $year,
        public readonly int $amountInCents,
        public readonly ?string $expenseTypeId,
        public readonly ?string $expenseTypeName,
        public readonly ?string $recurringExpenseId,
        public readonly ?string $recurringExpenseName,
        public readonly ?string $accountId,
        public readonly ?string $residentUnitId,
        public readonly float $confidence,
    ) {}
}
