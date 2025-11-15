<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\FindGasPrice\FindGasPriceQuery;
use App\Context\Gas\Domain\Exception\GasPriceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class FindGasPriceController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $query = new FindGasPriceQuery();

        $priceInCents = $this->ask($query);

        return new JsonResponse(
            [
                'price_per_m3_in_cents' => $priceInCents,
            ],
            Response::HTTP_OK,
        );
    }

    protected function exceptions(): array
    {
        return [
            GasPriceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
