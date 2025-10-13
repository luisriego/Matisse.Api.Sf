<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById\FindResidentUnitByIdQuery;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

final class GetResidentUnitByIdController extends ApiController
{
    public function __construct(private readonly MessageBusInterface $queryBus) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $query = new FindResidentUnitByIdQuery($id);
            $envelope = $this->queryBus->dispatch($query);
            $residentUnitData = $envelope->last(HandledStamp::class)->getResult();

            return new JsonResponse($residentUnitData, Response::HTTP_OK);
        } catch (ResourceNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
