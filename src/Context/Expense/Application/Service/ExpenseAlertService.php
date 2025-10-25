<?php

namespace App\Context\Expense\Application\Service;

use App\Shared\Application\TextGeneratorInterface;
use Psr\Log\LoggerInterface;

class ExpenseAlertService
{
    private TextGeneratorInterface $textGenerator;
    private LoggerInterface $logger;

    public function __construct(TextGeneratorInterface $textGenerator, LoggerInterface $logger)
    {
        $this->textGenerator = $textGenerator;
        $this->logger = $logger;
    }

    /**
     * Genera un aviso si el valor de un gasto está fuera de un rango esperado.
     *
     * @param string $expenseType El tipo de gasto (ej. "cuentas del edificio").
     * @param float $amount El monto del gasto.
     * @param float $minExpected El valor mínimo esperado.
     * @param float $maxExpected El valor máximo esperado.
     * @return string|null Un mensaje de aviso generado por la IA, o null si el monto está dentro del rango.
     */
    public function generateExpenseAnomalyAlert(
        string $expenseType,
        float $amount,
        float $minExpected,
        float $maxExpected
    ): ?string {
        if ($amount >= $minExpected && $amount <= $maxExpected) {
            return null; // El monto está dentro del rango esperado, no se necesita aviso.
        }

        $anomalyType = $amount < $minExpected ? 'demasiado bajo' : 'demasiado alto';
        $expectedRange = "entre {$minExpected} y {$maxExpected}";

        $prompt = "El gasto de '{$expenseType}' tiene un valor de {$amount}, lo cual es {$anomalyType}. " .
            "Normalmente, este gasto está {$expectedRange}. " .
            "Genera un aviso conciso y profesional para un gerente, explicando la anomalía y sugiriendo una posible acción o revisión. " .
            "No uses introducciones como 'Estimado gerente', ve directo al punto.";

        $this->logger->info("Generando alerta de anomalía para gasto: {$expenseType} con monto {$amount}");

        $alertMessage = $this->textGenerator->generate($prompt);

        if ($alertMessage === null) {
            $this->logger->error("Fallo al generar el mensaje de alerta para la anomalía del gasto.");
            return "No se pudo generar un aviso de anomalía para el gasto de '{$expenseType}'.";
        }

        return $alertMessage;
    }
}