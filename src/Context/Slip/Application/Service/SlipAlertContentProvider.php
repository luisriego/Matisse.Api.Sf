<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Service;

final class SlipAlertContentProvider
{
    public function provide(
        string $anomalyType,
        string $amountStr,
        string $minStr,
        string $maxStr
    ): array {
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
