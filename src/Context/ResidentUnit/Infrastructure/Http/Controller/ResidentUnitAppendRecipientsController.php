<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\AppendRecipients\AppendRecipientsCommand;
use App\Context\ResidentUnit\Application\UseCase\AppendRecipients\AppendRecipientsCommandHandler;
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitNotFoundException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\AppendRecipientsRequestDto;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResidentUnitAppendRecipientsController
{
    public function __construct(private AppendRecipientsCommandHandler $commandHandler) {}

    /**
     * @throws ResidentUnitNotFoundException
     */
    public function __invoke(AppendRecipientsRequestDto $requestDto): Response
    {
        $command = new AppendRecipientsCommand(
            $requestDto->id,
            $requestDto->name,
            $requestDto->email,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_OK);
    }
}
