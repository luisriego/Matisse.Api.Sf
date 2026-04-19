<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto;

/**
 * Rule-based classification of Brazilian bank CREDIT memos into {@see ConfirmLineDto} creditKind values.
 *
 * "Other" patterns are checked first so yields/interests are not mistaken for boleto settlement.
 */
final readonly class CreditMemoClassifier
{
    private const float HIGH = 0.92;

    /**
     * @return array{creditKind: string|null, confidence: float, source: string|null}
     */
    public function classify(string $memo): array
    {
        $n = $this->normalize($memo);

        foreach (self::otherSubstrings() as $needle) {
            if (str_contains($n, $needle)) {
                return [
                    'creditKind' => ConfirmLineDto::CREDIT_KIND_OTHER,
                    'confidence' => self::HIGH,
                    'source'     => 'memo_pattern:other:' . $needle,
                ];
            }
        }

        foreach (self::settlementSubstrings() as $needle) {
            if (str_contains($n, $needle)) {
                return [
                    'creditKind' => ConfirmLineDto::CREDIT_KIND_BOLETO_SETTLEMENT,
                    'confidence' => self::HIGH,
                    'source'     => 'memo_pattern:settlement:' . $needle,
                ];
            }
        }

        return [
            'creditKind' => null,
            'confidence' => 0.0,
            'source'     => null,
        ];
    }

    private function normalize(string $memo): string
    {
        $u = mb_strtoupper(trim($memo), 'UTF-8');
        // Fold common accents for matching (BRL bank strings vary)
        return str_replace(
            ['Á', 'À', 'Ã', 'Â', 'É', 'Ê', 'Í', 'Ó', 'Ô', 'Õ', 'Ú', 'Ü', 'Ç'],
            ['A', 'A', 'A', 'A', 'E', 'E', 'I', 'O', 'O', 'O', 'U', 'U', 'C'],
            $u,
        );
    }

    /**
     * @return list<string>
     */
    private static function otherSubstrings(): array
    {
        return [
            'RENDIMENTO',
            'RENDIMENTOS',
            'REND PAGO',
            'APLIC AUT',
            'APLICACAO AUT',
            'POUPANCA',
            'POUPANÇA',
            'JUROS',
            ' JURO',
            'ESTORNO',
            'IOF',
            'DEVOL',
            'RESGATE',
            'CREDITO TRIBUTARIO',
            'SALDO APLIC',
            'AUT MAIS',
            'PAGO APLIC',
        ];
    }

    /**
     * @return list<string>
     */
    private static function settlementSubstrings(): array
    {
        return [
            'BOLETOS RECEBIDOS',
            'BOLETO RECEBIDO',
            'RECEBIMENTO BOLETO',
            'RECEB BOLETO',
            'COBRANCA RECEBIDA',
            'COBRANÇA RECEBIDA',
            'LIQUIDACAO BOLETO',
            'LIQUIDAÇÃO BOLETO',
            'LIQ BOLETO',
            'COMPE ', // e.g. COMPE BACEN — trailing space reduces false positives
            ' COMPE',
        ];
    }
}
