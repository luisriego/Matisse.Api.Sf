<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Infrastructure\Http\Dto\SlipCheckTotalRequestDto;
use App\Shared\Domain\Exception\AIServiceUnavailableException;
use App\Shared\Infrastructure\Symfony\ApiController;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class SlipCheckTotalPostController extends ApiController
{
    public function __invoke(#[MapRequestPayload] SlipCheckTotalRequestDto $dto): JsonResponse
    {
        $result = $this->ask($dto->toCommand());

        if (null === $result) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            RuntimeException::class => Response::HTTP_BAD_REQUEST,
            AIServiceUnavailableException::class => Response::HTTP_SERVICE_UNAVAILABLE,
        ];
    }
}
