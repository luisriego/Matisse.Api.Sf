<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\SendBulkSlips\SendBulkSlipsCommand;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_array;

#[OA\Post(
    path: '/api/v1/slips/bulk-send',
    summary: 'Send multiple slips in bulk',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['slip_ids'],
            properties: [
                new OA\Property(property: 'slip_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
            ],
        ),
    ),
    tags: ['Slips'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 202, description: 'Bulk send accepted.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class SlipsBulkSendPostController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $slipIds = $data['slip_ids'] ?? null;

        if (null === $slipIds || !is_array($slipIds)) {
            return new JsonResponse(['error' => 'The field "slip_ids" is required and must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $this->dispatch(new SendBulkSlipsCommand($slipIds));

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }

    public function exceptions(): array
    {
        return [];
    }
}
