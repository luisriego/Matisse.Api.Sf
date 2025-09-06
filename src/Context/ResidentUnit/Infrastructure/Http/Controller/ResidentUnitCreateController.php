<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommand;
use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommandHandler;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\CreateResidentUnitRequestDto;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResidentUnitCreateController
{
    public function __construct(private CreateResidentUnitCommandHandler $commandHandler) {}

    public function __invoke(CreateResidentUnitRequestDto $requestDto): Response
    {
        $command = new CreateResidentUnitCommand(
            $requestDto->id,
            $requestDto->unit,
            $requestDto->idealFraction,
            $requestDto->notificationRecipients,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
