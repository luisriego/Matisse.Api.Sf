<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients\CreateResidentUnitWithRecipientsCommand;
use App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients\CreateResidentUnitWithRecipientsCommandHandler;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\CreateResidentUnitWithRecipientsRequestDto;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResidentUnitCreateWithRecipientsController
{
    public function __construct(private CreateResidentUnitWithRecipientsCommandHandler $commandHandler) {}

    public function __invoke(CreateResidentUnitWithRecipientsRequestDto $requestDto): Response
    {
        $command = new CreateResidentUnitWithRecipientsCommand(
            $requestDto->id,
            $requestDto->unit,
            $requestDto->idealFraction,
            $requestDto->notificationRecipients,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
