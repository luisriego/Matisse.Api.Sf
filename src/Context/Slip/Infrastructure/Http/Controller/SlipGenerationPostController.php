<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Infrastructure\Http\Dto\SlipGenerationRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

#[OA\Post(
    path: '/api/v1/slips/generation',
    summary: 'Generate monthly slips',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['targetMonth'],
            properties: [
                new OA\Property(property: 'targetMonth', type: 'string', pattern: '^\d{4}-\d{2}$', example: '2026-03'),
                new OA\Property(property: 'force', type: 'boolean', default: false),
                new OA\Property(property: 'extraFee', type: 'integer', default: 0, description: 'Extra fee per unit in cents'),
                new OA\Property(property: 'reserveFund', type: 'integer', default: 0, description: 'Reserve fund per unit in cents'),
            ],
        ),
    ),
    tags: ['Slips'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Slips generated.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class SlipGenerationPostController extends ApiController
{
    private readonly SlipRepository $slipRepository;

    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
        SlipRepository $slipRepository,
    ) {
        parent::__construct($commandBus, $queryBus);
        $this->slipRepository = $slipRepository;
    }

    public function __invoke(SlipGenerationRequestDto $request): JsonResponse
    {
        $command = $request->toCommand();
        $this->dispatch($command);

        $dueYear = $request->year;
        $dueMonth = $request->month + 1;

        if ($dueMonth > 12) {
            $dueYear++;
            $dueMonth = 1;
        }

        $slips = $this->slipRepository->findByMonthYear($dueYear, $dueMonth);

        $payload = [];

        foreach ($slips as $slip) {
            $ru = $slip->residentUnit();
            $payload[] = [
                'id' => $slip->id(),
                'amount' => $slip->amount(),
                'status' => $slip->getStatus(),
                'dueDate' => $slip->dueDate()->format('Y-m-d'),
                'residentUnit' => [
                    'id' => $ru->id(),
                    'unit' => $ru->unit(),
                ],
            ];
        }

        return new JsonResponse(['slips' => $payload], Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            BadRequestHttpException::class => Response::HTTP_BAD_REQUEST,
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
