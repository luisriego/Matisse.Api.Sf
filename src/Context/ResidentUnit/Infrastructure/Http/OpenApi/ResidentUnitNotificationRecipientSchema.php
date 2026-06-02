<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ResidentUnitNotificationRecipient',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'João Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao.silva@example.com'),
    ],
)]
final class ResidentUnitNotificationRecipientSchema {}
