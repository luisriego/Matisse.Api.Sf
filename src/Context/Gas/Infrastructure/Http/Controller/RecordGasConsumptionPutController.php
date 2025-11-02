<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\RecordGasConsumption\RecordGasConsumptionCommandHandler;
use App\Context\Gas\Infrastructure\Http\Dto\RecordGasConsumptionRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final readonly class RecordGasConsumptionPutController
{
    public function __construct(private RecordGasConsumptionCommandHandler $commandHandler)
    {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(#[MapRequestPayload] RecordGasConsumptionRequestDto $dto): Response
    {
        $command = $dto->toCommand();

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}