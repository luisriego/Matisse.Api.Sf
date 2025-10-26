<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Service;

use App\Shared\Application\TextGeneratorInterface;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use Psr\Log\LoggerInterface;

class SlipAlertService
{
    private TextGeneratorInterface $textGenerator;
    private LoggerInterface $logger;

    public function __construct(TextGeneratorInterface $textGenerator, LoggerInterface $logger)
    {
        $this->textGenerator = $textGenerator;
        $this->logger = $logger;
    }

    /**
     * Genera un aviso si el total de un slip está fuera de un rango esperado.
     *
     * @param SlipAmount $amount El monto total del slip (en centavos).
     */
    public function checkAndGenerateAnomalyAlert(
        SlipAmount $amount
    ): ?string {

        // --- Lógica Correcta ---
        $minExpected = new SlipAmount(500000);  // Representa 5000.00
        $maxExpected = new SlipAmount(1000000); // Representa 10000.00

        if ($amount->value() >= $minExpected->value() && $amount->value() <= $maxExpected->value()) {
            return null;
        }

        $anomalyType = $amount->value() < $minExpected->value() ? 'muito baixo' : 'muito alto';

        $amountStr = 'R$ ' . number_format($amount->value() / 100, 2, ',', '.');
        $minStr = 'R$ ' . number_format($minExpected->value() / 100, 2, ',', '.');
        $maxStr = 'R$ ' . number_format($maxExpected->value() / 100, 2, ',', '.');

        if ($anomalyType === 'muito baixo') {
            $diagInstruction = "O valor total do slip ({$amountStr}) está abaixo do esperado (mínimo de {$minStr}). Isso geralmente indica que faltam despesas a serem registradas ou houve um erro de digitação para menos.";
            $recomInstruction = "Revise o slip para garantir que todas as despesas foram incluídas corretamente.";
            $titleInstruction = "Alerta: Total de Slip Baixo";
        } else { // 'muito alto'
            $diagInstruction = "O valor total do slip ({$amountStr}) está acima do esperado (máximo de {$maxStr}). Isso pode significar despesas excessivas, um desvio de orçamento ou um erro de digitação para mais.";
            $recomInstruction = "Analise as despesas do slip para identificar a causa do valor elevado e verificar se está justificado.";
            $titleInstruction = "Alerta: Total de Slip Elevado";
        }

        $prompt = <<<PROMPT
Você é um assistente que formata respostas em JSON.
Sua única tarefa é gerar uma resposta em formato JSON.
NÃO adicione explicações. NÃO use \n. NÃO use placeholders como [Nome].
O contexto é uma EMPRESA, não um condomínio.

Gere um JSON com três chaves: "titulo", "diagnostico", e "recomendacao".

1.  **titulo:** Use este texto EXATO: "{$titleInstruction}"
2.  **diagnostico:** Use este texto EXATO: "{$diagInstruction}"
3.  **recomendacao:** Use este texto EXATO: "{$recomInstruction}"

A resposta DEVE ser apenas o JSON.
PROMPT;

        $this->logger->info("Generating anomaly alert for slip total with amount {$amount->value()}");

        $alertMessage = $this->textGenerator->generate($prompt);

        if ($alertMessage === null) {
            $this->logger->error("Text generator (Gemma) returned null for slip anomaly.");
            return null;
        }

        $decodedJson = json_decode($alertMessage);

        if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error(
                "Failed to decode JSON from text generator. Invalid JSON received.",
                [
                    'json_last_error' => json_last_error_msg(),
                    'raw_response' => $alertMessage
                ]
            );
            return null;
        }

        if (!isset($decodedJson->titulo) || !isset($decodedJson->diagnostico) || !isset($decodedJson->recomendacao)) {
            $this->logger->error(
                "JSON response from text generator is missing required keys.",
                [
                    'received_object' => $decodedJson
                ]
            );
            return null;
        }

        return $decodedJson;
    }
}