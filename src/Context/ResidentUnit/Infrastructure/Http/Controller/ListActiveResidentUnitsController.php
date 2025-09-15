<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\ListActive\ListActiveResidentUnitsQuery;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

final class ListActiveResidentUnitsController extends ApiController
{
    public function __construct(
        MessageBusInterface                  $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): JsonResponse
    {
        $query = new ListActiveResidentUnitsQuery();

        $envelope = $this->queryBus->dispatch($query);
        $expensesData = $envelope->last(HandledStamp::class)->getResult();

        $residentUnits = (array) $this->ask($query);
//
        $data = array_map(static fn (ResidentUnit $residentUnit) => [
            'id' => $residentUnit->id(),
            'unit' => $residentUnit->unit(),
            'idealFraction' => $residentUnit->idealFraction()
        ], $expensesData);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    protected function exceptions(): array
    {
        return [];
    }
}
