<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommand;
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitAlreadyExistsException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\CreateResidentUnitRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ResidentUnitCreateController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(CreateResidentUnitRequestDto $requestDto): Response
    {
        $command = new CreateResidentUnitCommand(
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
