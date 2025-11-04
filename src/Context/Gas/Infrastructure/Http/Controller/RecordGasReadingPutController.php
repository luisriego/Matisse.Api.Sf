<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommandHandler;
use App\Context\Gas\Infrastructure\Http\Dto\RecordGasReadingRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final readonly class RecordGasReadingPutController
{
    public function __construct(private RecordGasReadingCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(#[MapRequestPayload] RecordGasReadingRequestDto $dto): Response
    {
        $command = $dto->toCommand();

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
