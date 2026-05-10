<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\FindSlipsByMonth\FindSlipsByMonthQuery;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_map;
use function explode;
use function is_string;
use function preg_match;

final class FindSlipsByMonthGetController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $monthValue = $request->query->get('targetMonth');

        if (!is_string($monthValue) || !preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            throw new InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        [$year, $month] = array_map('intval', explode('-', $monthValue));

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid month. Must be between 01 and 12.');
        }

        $slips = $this->ask(new FindSlipsByMonthQuery($year, $month));

        return new JsonResponse(['slips' => $slips], Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
