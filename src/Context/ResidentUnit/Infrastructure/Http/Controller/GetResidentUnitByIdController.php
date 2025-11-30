<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById\FindResidentUnitByIdQuery;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class GetResidentUnitByIdController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $id): JsonResponse
    {
        $residentUnitData = $this->ask(new FindResidentUnitByIdQuery($id));

        return new JsonResponse($residentUnitData, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
