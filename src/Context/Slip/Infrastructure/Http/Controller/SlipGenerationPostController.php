<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Infrastructure\Http\Dto\SlipGenerationRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SlipGenerationPostController extends ApiController
{
    public function __invoke(SlipGenerationRequestDto $request): Response
    {
        $command = $request->toCommand();
        $this->dispatch($command);

        return new Response('', Response::HTTP_CREATED);
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
