<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\PatchIdealFractionRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class ResidentUnitIdealFractionPatchController extends ApiController
{
    public function __invoke(string $id, #[MapRequestPayload] PatchIdealFractionRequestDto $request): JsonResponse
    {
        $this->dispatch($request->toCommand($id));

        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            IdealFractionSumExceedsLimitException::class => Response::HTTP_CONFLICT,
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
