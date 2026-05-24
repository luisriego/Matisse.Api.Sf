<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Infrastructure\Http\Controller;

use App\Context\BillingPolicy\Application\UseCase\ResolveBillingPolicy\ResolveBillingPolicyQuery;
use App\Context\BillingPolicy\Domain\ResolvedBillingPolicy;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_string;
use function preg_match;

#[OA\Get(
    path: '/api/v1/billing-policy/resolve',
    summary: 'Resolve billing policy for a target month',
    tags: ['Billing Policy'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'targetMonth',
            in: 'query',
            required: true,
            schema: new OA\Schema(type: 'string', example: '2026-01'),
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Resolved billing policy.'),
        new OA\Response(response: 400, description: 'Invalid targetMonth.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class BillingPolicyResolveGetController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $targetMonth = $request->query->get('targetMonth')
            ?? $request->query->get('target_month');

        if (!is_string($targetMonth) || 1 !== preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            throw new InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        /** @var ResolvedBillingPolicy $resolved */
        $resolved = $this->ask(new ResolveBillingPolicyQuery($targetMonth));

        return new JsonResponse(['data' => $resolved->toArray()]);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
