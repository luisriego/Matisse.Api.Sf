<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\ResidentUnit\UnlinkResidentUnitFromUserCommand;
use App\Context\User\Application\UseCase\ResidentUnit\UnlinkResidentUnitFromUserCommandHandler;
use Symfony\Component\HttpFoundation\Response;

final readonly class UnlinkResidentUnitFromUserDeleteController
{
    public function __construct(private UnlinkResidentUnitFromUserCommandHandler $handler) {}

    public function __invoke(string $id): Response
    {
        $this->handler->__invoke(new UnlinkResidentUnitFromUserCommand($id));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}