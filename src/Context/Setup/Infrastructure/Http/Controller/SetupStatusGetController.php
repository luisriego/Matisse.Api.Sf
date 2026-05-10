<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\GetSetupStatus\GetSetupStatusQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class SetupStatusGetController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->ask(new GetSetupStatusQuery()), Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
