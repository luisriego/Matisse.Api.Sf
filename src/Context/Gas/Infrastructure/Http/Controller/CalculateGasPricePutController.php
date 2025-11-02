<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommandHandler;
use App\Context\Gas\Infrastructure\Http\Dto\DefineGasPriceRequestDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

 // <-- 1. Importar el atributo

final readonly class CalculateGasPricePutController
{
    public function __construct(private DefineGasPriceCommandHandler $commandHandler) {}

    public function __invoke(#[MapRequestPayload] DefineGasPriceRequestDto $request): Response
    {
        $command = $request->toCommand();

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
