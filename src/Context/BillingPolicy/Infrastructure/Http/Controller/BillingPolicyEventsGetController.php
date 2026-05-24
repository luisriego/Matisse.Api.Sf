<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Infrastructure\Http\Controller;

use App\Context\BillingPolicy\Application\UseCase\ListBillingPolicyEvents\ListBillingPolicyEventsQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[OA\Get(
    path: '/api/v1/billing-policy/events',
    summary: 'List billing policy events',
    tags: ['Billing Policy'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'limit',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer', default: 50),
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Event list.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class BillingPolicyEventsGetController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 50);

        /** @var list<array<string, mixed>> $events */
        $events = $this->ask(new ListBillingPolicyEventsQuery($limit));

        return new JsonResponse(['events' => $events]);
    }

    public function exceptions(): array
    {
        return [];
    }
}
