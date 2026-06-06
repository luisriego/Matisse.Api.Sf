<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\ConfirmationResend\ResendConfirmationEmailCommand;
use App\Context\User\Application\UseCase\ConfirmationResend\ResendConfirmationEmailCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\ResendConfirmationEmailRequestDto;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Post(
    path: '/api/v1/users/confirmation-resend',
    summary: 'Resend account confirmation email',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
            ],
        ),
    ),
    tags: ['Users'],
    security: [],
    responses: [
        new OA\Response(response: 200, description: 'Request accepted (always returns success to prevent enumeration).'),
        new OA\Response(response: 400, description: 'Invalid request body.'),
    ],
)]
final readonly class ResendConfirmationEmailController
{
    public function __construct(
        private ResendConfirmationEmailCommandHandler $commandHandler,
    ) {}

    public function __invoke(ResendConfirmationEmailRequestDto $requestDto): JsonResponse
    {
        ($this->commandHandler)(new ResendConfirmationEmailCommand($requestDto->email()));

        return new JsonResponse(
            ['message' => 'Se o seu e-mail estiver registrado e a conta ainda não foi ativada, você receberá um novo link de confirmação.'],
            Response::HTTP_OK,
        );
    }
}
