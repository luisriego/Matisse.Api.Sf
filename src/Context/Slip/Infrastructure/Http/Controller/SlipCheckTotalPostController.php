<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Infrastructure\Http\Dto\SlipCheckTotalRequestDto;
use App\Shared\Application\TextGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SlipCheckTotalPostController
{
    public function __construct(
        private TextGeneratorInterface $textGenerator,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = SlipCheckTotalRequestDto::fromRequest($request);
            $amount = $dto->amount; // $amount es un objeto SlipAmount
        } catch (\RuntimeException $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], Response::HTTP_BAD_REQUEST);
        }

        // --- LÓGICA DE NEGOCIO DIRECTAMENTE AQUÍ ---

        // 1. Definimos los límites en centavos.
        $minExpected = new SlipAmount(500000);
        $maxExpected = new SlipAmount(1000000);

        // 2. Comparamos.
        if ($amount->value() >= $minExpected->value() && $amount->value() <= $maxExpected->value()) {
            // Si está en el rango, devolvemos 'ok'.
            return new JsonResponse([
                'status' => 'ok',
                'message' => 'O total do slip está dentro do intervalo esperado.',
                'amount' => $amount->value(),
            ], Response::HTTP_OK);
        }

        // 3. Si está fuera de rango, generamos el prompt y llamamos a la IA.
        $anomalyType = $amount->value() < $minExpected->value() ? 'muito baixo' : 'muito alto';
        $amountForPrompt = number_format($amount->value() / 100, 2, ',', '.');
        $minForPrompt = number_format($minExpected->value() / 100, 2, ',', '.');
        $maxForPrompt = number_format($maxExpected->value() / 100, 2, ',', '.');
        $expectedRange = "entre {$minForPrompt} e {$maxForPrompt}";

        $prompt = "Um total de {$amountForPrompt} foi registrado, o que é {$anomalyType}. " .
            "Normalmente, totais similares estão {$expectedRange}. " .
            "atue como o cntador de un condomínio com experiencia." .
            "Gere um aviso conciso e profissional pero amigavel para o síndico do seu condominio, explicando a anomalia e sugerindo uma possível ação ou revisão. " .
            "quanto mais perto dos valores de referência, menor quantidade de contas podem estar faltando" .
            "Não use introduções como 'Prezado gerente', vá direto ao ponto.";

        $this->logger->info("Generating anomaly alert for slip total with amount {$amount->value()}");

        $alertMessage = $this->textGenerator->generate($prompt);

        if ($alertMessage === null) {
            $this->logger->error("Failed to generate alert message for slip total anomaly.");
            $alertMessage = "Não foi possível gerar um aviso de anomalia para o total.";
        }

        // 4. Devolvemos la alerta.
        return new JsonResponse([
            'status' => 'alert_generated',
            'message' => $alertMessage,
            'amount' => $amount->value(),
        ], Response::HTTP_OK);
    }
}