<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Service;

use function number_format;

final class SlipAlertContentProvider
{
    public function provide(
        string $anomalyType,
        int $amount,
        int $min,
        int $max,
    ): array {
        $amountStr = number_format($amount / 100, 2, ',', '.');
        $minStr = number_format($min / 100, 2, ',', '.');
        $maxStr = number_format($max / 100, 2, ',', '.');

        if ($anomalyType === 'muito baixo') {
            return [
                'title' => 'Alerta: Total de Slip Baixo',
                'diagnosis' => "O valor total do slip ({$amountStr}) está abaixo do esperado (mínimo de {$minStr}). Isso geralmente indica que faltam despesas a serem registradas ou houve um erro de digitação para menos.",
                'recommendation' => 'Revise o slip para garantir que todas as despesas foram incluídas corretamente.',
            ];
        }

        // 'muito alto'
        return [
            'title' => 'Alerta: Total de Slip Elevado',
            'diagnosis' => "O valor total do slip ({$amountStr}) está acima do esperado (máximo de {$maxStr}). Isso pode significar despesas excessivas, um desvio de orçamento ou um erro de digitação para mais.",
            'recommendation' => 'Analise as despesas do slip para identificar a causa do valor elevado e verificar se está justificado.',
        ];
    }
}
