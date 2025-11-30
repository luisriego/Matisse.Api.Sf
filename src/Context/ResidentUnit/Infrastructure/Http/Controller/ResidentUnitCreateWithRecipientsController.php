<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients\CreateResidentUnitWithRecipientsCommand;
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitAlreadyExistsException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\CreateResidentUnitWithRecipientsRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\Response;

final class ResidentUnitCreateWithRecipientsController extends ApiController
{
    public function __invoke(CreateResidentUnitWithRecipientsRequestDto $requestDto): Response
    {
        $command = new CreateResidentUnitWithRecipientsCommand(
            $requestDto->id,
            $requestDto->unit,
            $requestDto->idealFraction,
            $requestDto->notificationRecipients,
        );

        $this->dispatch($command);

        return new Response('', Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            ResidentUnitAlreadyExistsException::class => Response::HTTP_CONFLICT,
        ];
    }
}
