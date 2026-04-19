<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use OpenApi\Attributes as OA;

/**
 * A past income entry that resembles the current bank CREDIT memo (history-based hint).
 */
#[OA\Schema(
    schema: 'IncomePastAssignment',
    properties: [
        new OA\Property(property: 'month',              type: 'integer', example: 3),
        new OA\Property(property: 'year',               type: 'integer', example: 2026),
        new OA\Property(property: 'amountInCents',      type: 'integer', example: 49),
        new OA\Property(property: 'incomeTypeId',       type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'incomeTypeName',     type: 'string', nullable: true),
        new OA\Property(property: 'inferredCreditKind', type: 'string', enum: ['boleto_settlement', 'other'],
            description: 'Derived from the stored income description (e.g. consolidated boleto title).'),
        new OA\Property(property: 'confidence',         type: 'number', format: 'float', example: 0.72),
    ],
)]
final readonly class IncomePastAssignmentDto
{
    public function __construct(
        public int $month,
        public int $year,
        public int $amountInCents,
        public ?string $incomeTypeId,
        public ?string $incomeTypeName,
        public string $inferredCreditKind,
        public float $confidence,
    ) {}
}
