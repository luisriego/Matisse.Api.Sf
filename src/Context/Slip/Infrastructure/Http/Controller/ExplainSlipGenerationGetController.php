<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Infrastructure\Http\Dto\ExplainSlipGenerationRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

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
