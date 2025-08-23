<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\SendSlip\SlipSendCommand;
use App\Context\Slip\Domain\Exception\SlipNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\Exception\LogicException;

final class SlipSendPatchController extends ApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatch(new SlipSendCommand($id));

        return new JsonResponse('', Response::HTTP_ACCEPTED);
    }

    protected function exceptions(): array
    {
        return [
            SlipNotFoundException::class => Response::HTTP_NOT_FOUND,
            LogicException::class => Response::HTTP_CONFLICT, // Workflow transition not allowed
        ];
    }
}
