<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommand;
use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\CreateResidentUnitRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Put(
    path: '/api/v1/resident-unit/create',
    summary: 'Create resident unit',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'unit', 'idealFraction', 'email'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'unit', type: 'string', example: 'Apto. 401'),
                new OA\Property(property: 'idealFraction', type: 'number', format: 'float', example: 0.145678),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'residente@example.com'),
                new OA\Property(property: 'name', type: 'string', nullable: true, example: 'João Silva'),
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
final class ResidentUnitCreateController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(CreateResidentUnitRequestDto $requestDto): Response
    {
        $command = new CreateResidentUnitCommand(
            $requestDto->id,
            $requestDto->unit,
            $requestDto->idealFraction,
            $requestDto->email,
            $requestDto->name,
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
