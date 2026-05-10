<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\FinalizeSetup\FinalizeSetupCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class SetupFinalizePostController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $this->dispatch(new FinalizeSetupCommand());

        return new JsonResponse(['setupFinalized' => true], Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
