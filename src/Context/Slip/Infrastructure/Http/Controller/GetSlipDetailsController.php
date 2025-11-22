<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\GetSlipDetails\GetSlipDetailsQuery;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetSlipDetailsController extends ApiController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(string $id): JsonResponse
    {
        $query = new GetSlipDetailsQuery($id);
        
        $slipDetails = $this->ask($query);

        return new JsonResponse(
            $slipDetails,
            Response::HTTP_OK
        );
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
