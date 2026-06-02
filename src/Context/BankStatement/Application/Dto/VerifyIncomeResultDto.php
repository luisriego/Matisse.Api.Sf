<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'VerifyIncomeResult',
    properties: [
        new OA\Property(property: 'expectedInCents', type: 'integer', example: 50000),
        new OA\Property(property: 'receivedInCents', type: 'integer', example: 50000),
        new OA\Property(property: 'differenceInCents', type: 'integer', example: 0),
        new OA\Property(property: 'status', type: 'string', enum: ['balanced', 'shortfall', 'surplus'], example: 'balanced'),
        new OA\Property(property: 'totalSlips', type: 'integer', example: 2),
        new OA\Property(property: 'paidSlips', type: 'integer', example: 2),
        new OA\Property(
            property: 'unpaidSlips',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/UnpaidSlip'),
        ),
    ],
)]
final readonly class VerifyIncomeResultDto
{
    public const STATUS_BALANCED  = 'balanced';
    public const STATUS_SHORTFALL = 'shortfall';
    public const STATUS_SURPLUS   = 'surplus';

    /**
     * @param UnpaidSlipDto[] $unpaidSlips slips that are not yet paid (empty when balanced/surplus)
     */
    public function __construct(
        public readonly int $expectedInCents,
        public readonly int $receivedInCents,
        public readonly int $differenceInCents,
        public readonly string $status,
        public readonly int $totalSlips,
        public readonly int $paidSlips,
        public readonly array $unpaidSlips,
    ) {}
}
