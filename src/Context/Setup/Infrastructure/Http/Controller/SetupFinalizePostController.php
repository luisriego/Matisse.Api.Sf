<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\FinalizeSetup\FinalizeSetupCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Post(
    path: '/api/v1/setup/finalize',
    summary: 'Finalize condominium setup wizard',
    tags: ['Setup'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Setup finalized.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class SetupFinalizePostController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $this->dispatch(new FinalizeSetupCommand());

        return new JsonResponse(['setupFinalized' => true], Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
