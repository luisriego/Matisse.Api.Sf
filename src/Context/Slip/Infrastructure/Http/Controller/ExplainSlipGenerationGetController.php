<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Infrastructure\Http\Dto\ExplainSlipGenerationRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

#[OA\Get(
    path: '/api/v1/slips/generation/explain',
    summary: 'Explain slip generation breakdown for a month',
    tags: ['Slips'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'targetMonth', in: 'query', required: true, schema: new OA\Schema(type: 'string', pattern: '^\d{4}-\d{2}$')),
        new OA\Parameter(name: 'extraFee', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0),
            description: 'Extra fee per unit in cents'),
        new OA\Parameter(name: 'reserveFund', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0),
            description: 'Reserve fund per unit in cents'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Generation explanation.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ExplainSlipGenerationGetController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(ExplainSlipGenerationRequestDto $request): JsonResponse
    {
        $payload = $this->ask($request->toQuery());

        return new JsonResponse($payload, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            BadRequestHttpException::class => Response::HTTP_BAD_REQUEST,
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
