<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UnpaidSlip',
    properties: [
        new OA\Property(property: 'slipId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'amountInCents', type: 'integer', example: 25000),
        new OA\Property(property: 'status', type: 'string', example: 'issued'),
        new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'dueDate', type: 'string', format: 'date', example: '2026-03-10'),
    ],
)]
final readonly class UnpaidSlipDto
{
    public function __construct(
        public readonly string $slipId,
        public readonly int $amountInCents,
        public readonly string $status,
        public readonly string $residentUnitId,
        public readonly string $dueDate,
    ) {}
}
