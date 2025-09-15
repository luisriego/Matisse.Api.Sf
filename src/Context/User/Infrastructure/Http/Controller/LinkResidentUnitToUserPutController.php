<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\ResidentUnit\LinkResidentUnitToUserCommand;
use App\Context\User\Application\UseCase\ResidentUnit\LinkResidentUnitToUserCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\LinkResidentUnitToUserRequestDto;
use Symfony\Component\HttpFoundation\Response;

final readonly class LinkResidentUnitToUserPutController
{
    public function __construct(private LinkResidentUnitToUserCommandHandler $handler) {}

    public function __invoke(string $id, LinkResidentUnitToUserRequestDto $request): Response
    {
        if ($request->residentUnitId === null || $request->residentUnitId === '') {
            throw new \InvalidArgumentException('residentUnitId is required');
        }

        $command = new LinkResidentUnitToUserCommand(
            $id,
            $request->residentUnitId,
        );

        $this->handler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
