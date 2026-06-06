<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients\CreateResidentUnitWithRecipientsCommand;
use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\CreateResidentUnitWithRecipientsRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Put(
    path: '/api/v1/resident-unit/create-with-recipients',
    summary: 'Create resident unit with notification recipients',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'unit', 'idealFraction', 'notificationRecipients'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'unit', type: 'string', example: '101'),
                new OA\Property(property: 'idealFraction', type: 'number', format: 'float'),
                new OA\Property(
                    property: 'notificationRecipients',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ResidentUnitNotificationRecipient'),
                ),
            ],
        ),
    ),
    tags: ['Resident Units'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Resident unit created. Empty response body.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 409, description: 'Ideal fraction sum exceeds limit.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ResidentUnitCreateWithRecipientsController extends ApiController
{
    public function __invoke(CreateResidentUnitWithRecipientsRequestDto $requestDto): Response
    {
        $command = new CreateResidentUnitWithRecipientsCommand(
            $requestDto->id,
            $requestDto->unit,
            $requestDto->idealFraction,
            $requestDto->notificationRecipients,
        );

        $this->dispatch($command);

        return new Response('', Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            IdealFractionSumExceedsLimitException::class => Response::HTTP_CONFLICT,
        ];
    }
}
