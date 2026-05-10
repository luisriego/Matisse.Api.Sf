<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Infrastructure\Http\Dto\SlipGenerationRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

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
