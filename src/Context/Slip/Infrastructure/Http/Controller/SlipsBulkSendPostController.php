<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\SendBulkSlips\SendBulkSlipsCommand;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_array;

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

    protected function exceptions(): array
    {
        return [];
    }
}
