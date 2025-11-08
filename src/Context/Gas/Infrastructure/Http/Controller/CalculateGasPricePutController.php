<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Infrastructure\Http\Dto\DefineGasPriceRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class CalculateGasPricePutController extends ApiController
{
    public function __invoke(#[MapRequestPayload] DefineGasPriceRequestDto $request): JsonResponse
    {
        $this->dispatch($request->toCommand());

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    protected function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
