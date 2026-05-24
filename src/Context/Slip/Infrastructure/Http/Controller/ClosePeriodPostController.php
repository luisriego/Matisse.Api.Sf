<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\Exception\NoSlipsToCloseException;
use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Infrastructure\Http\Dto\ClosePeriodRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Post(
    path: '/api/v1/periods/{targetMonth}/close',
    summary: 'Close accounting period for a month',
    tags: ['Slips'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'targetMonth', in: 'path', required: true, schema: new OA\Schema(type: 'string', pattern: '^\d{4}-\d{2}$'),
            description: 'Month to close in YYYY-MM format'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Period closed successfully.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 409, description: 'Period already closed.'),
        new OA\Response(response: 422, description: 'No slips to close.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ClosePeriodPostController extends ApiController
{
    public function __invoke(ClosePeriodRequestDto $request): JsonResponse
    {
        $this->dispatch($request->toCommand());

        return new JsonResponse(
            ['message' => 'Period closed successfully.'],
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            PeriodAlreadyClosedException::class => Response::HTTP_CONFLICT,
            NoSlipsToCloseException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
