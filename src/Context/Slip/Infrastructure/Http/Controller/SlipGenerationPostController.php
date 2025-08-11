<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\SlipGenerationCommandHandler;
use App\Context\Slip\Infrastructure\Http\Dto\SlipGenerationRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;

final readonly class SlipGenerationPostController
{
    public function __construct(private SlipGenerationCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(SlipGenerationRequestDto $requestDto): Response
    {
        $this->commandHandler->__invoke($requestDto->toCommand());

        return new Response('', Response::HTTP_CREATED);
    }
}