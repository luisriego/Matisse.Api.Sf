<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ResidentUnit',
    description: 'Residential unit (apartment/lot) with ideal fraction and notification recipients.',
    required: ['id', 'unit', 'idealFraction', 'isActive', 'createdAt', 'notificationRecipients'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'unit', type: 'string', example: '101', description: 'Unit identifier within the building.'),
        new OA\Property(
            property: 'idealFraction',
            type: 'number',
            format: 'float',
            example: 0.125,
            description: 'Share of common costs (0–1). Sum of all active units must not exceed 1.',
        ),
        new OA\Property(property: 'isActive', type: 'boolean', example: true),
        new OA\Property(
            property: 'createdAt',
            type: 'string',
            example: '2026-05-30 12:00:00',
            description: 'Format: Y-m-d H:i:s',
        ),
        new OA\Property(
            property: 'updatedAt',
            type: 'string',
            nullable: true,
            example: '2026-05-30 14:30:00',
            description: 'Format: Y-m-d H:i:s, or null if never updated.',
        ),
        new OA\Property(
            property: 'notificationRecipients',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ResidentUnitNotificationRecipient'),
        ),
    ],
)]
final class ResidentUnitSchema {}
