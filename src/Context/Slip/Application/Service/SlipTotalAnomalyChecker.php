<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Service;

use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Shared\Application\TextGeneratorInterface;
use Psr\Log\LoggerInterface;

use function number_format;

final class SlipTotalAnomalyChecker
{
    public function __construct(
        private readonly TextGeneratorInterface $textGenerator,
        private readonly LoggerInterface $logger,
    ) {}

    public function check(SlipAmount $amount): SlipTotalCheckResult
    {
        // TODO: These values will be calculated dynamically in the future.
        $minExpected = 500000;
        $maxExpected = 1000000;

        if ($amount->value() >= $minExpected && $amount->value() <= $maxExpected) {
            return new SlipTotalCheckResult(
                'ok',
                'O total de gastos do slip está dentro do intervalo esperado.',
                $amount->value(),
            );
        }

        $anomalyType = $amount->value() < $minExpected ? 'muito baixo' : 'muito alto';
        $amountForPrompt = number_format($amount->value() / 100, 2, ',', '.');
        $minForPrompt = number_format($minExpected / 100, 2, ',', '.');
        $maxForPrompt = number_format($maxExpected / 100, 2, ',', '.');
        $expectedRange = "entre {$minForPrompt} e {$maxForPrompt}";

        $prompt = "O total de gastos do condomínio foi de {$amountForPrompt}, um valor considerado {$anomalyType}. "
            . "Normalmente, os gastos para um período similar estão {$expectedRange}. "
            . 'Atue como um contador experiente de um condomínio. '
            . 'Gere um aviso conciso, profissional e amigável para o síndico, explicando esta anomalia nos gastos e sugerindo uma possível causa ou ação de revisão (por exemplo, um gasto inesperado, um erro de lançamento ou uma conta faltante). '
            . "Não use introduções como 'Prezado gerente', vá direto ao ponto.";

        $this->logger->info("Generating anomaly alert for slip total with amount {$amount->value()}");

        $alertMessage = $this->textGenerator->generate($prompt);

        if ($alertMessage === null) {
            $this->logger->error('Failed to generate alert message for slip total anomaly.');
            $alertMessage = 'Não foi possível gerar um aviso de anomalia para o total de gastos.';
        }

        return new SlipTotalCheckResult(
            'alert_generated',
            $alertMessage,
            $amount->value(),
        );
    }
}
