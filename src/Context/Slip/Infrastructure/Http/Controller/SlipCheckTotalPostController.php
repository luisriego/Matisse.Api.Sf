<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\Service\SlipTotalAnomalyChecker;
use App\Context\Slip\Infrastructure\Http\Dto\SlipCheckTotalRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class SlipCheckTotalPostController
{
    public function __construct(private SlipTotalAnomalyChecker $anomalyChecker)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = SlipCheckTotalRequestDto::fromRequest($request);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->anomalyChecker->check($dto->amount);

        return new JsonResponse([
            'status' => $result->status,
            'message' => $result->message,
            'amount' => $result->amount,
        ], Response::HTTP_OK);
    }
}
