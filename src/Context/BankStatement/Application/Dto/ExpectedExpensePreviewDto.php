<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ExpectedExpensePreview',
    description: 'Suggested expectedExpense block for /bank/ofx-confirm (option B). Echo in confirm lines.',
    properties: [
        new OA\Property(property: 'recurringExpenseId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(
            property: 'createOrUpdate',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'displayName', type: 'string', example: 'Copasa'),
                new OA\Property(property: 'frequency', type: 'string', enum: ['monthly', 'annual', 'semi_annual', 'custom']),
                new OA\Property(property: 'amountKind', type: 'string', enum: ['fixed', 'variable']),
                new OA\Property(property: 'monthsOfYear', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
                new OA\Property(property: 'dueDay', type: 'integer', nullable: true, example: 10),
            ],
        ),
    ],
)]
final readonly class ExpectedExpensePreviewDto
{
    public function __construct(
        public ?string $recurringExpenseId,
        public ?ExpectedExpenseCreateOrUpdateDto $createOrUpdate,
    ) {}

    /**
     * @return array{recurringExpenseId: ?string, createOrUpdate: ?array<string, mixed>}
     */
    public function toArray(): array
    {
        $createOrUpdate = null;
        if ($this->createOrUpdate !== null) {
            $createOrUpdate = [
                'displayName' => $this->createOrUpdate->displayName,
                'frequency' => $this->createOrUpdate->frequency,
                'amountKind' => $this->createOrUpdate->amountKind,
                'monthsOfYear' => $this->createOrUpdate->monthsOfYear,
                'dueDay' => $this->createOrUpdate->dueDay,
            ];
        }

        return [
            'recurringExpenseId' => $this->recurringExpenseId,
            'createOrUpdate' => $createOrUpdate,
        ];
    }
}
