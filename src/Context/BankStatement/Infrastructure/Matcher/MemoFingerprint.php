<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Matcher;

use function array_filter;
use function array_slice;
use function array_values;
use function explode;
use function implode;
use function mb_strlen;
use function mb_strtolower;
use function preg_replace;
use function trim;

/**
 * Produces a stable fingerprint from an OFX MEMO field for historical matching.
 *
 * Strategy: lowercase → remove amounts/dates/codes → collapse whitespace → take first N words.
 * Example: "BOLETO PAGO ARTE DE LIMP ARTE DE LIMPAR LTDA 03.922.845/0001-22"
 *       → "boleto pago arte de limp"
 */
final class MemoFingerprint
{
    private const int MAX_WORDS = 5;

    public static function from(string $memo): string
    {
        $normalized = mb_strtolower($memo);

        // Remove CNPJ/CPF patterns (00.000.000/0001-00 | 000.000.000-00)
        $normalized = (string) preg_replace('/\d{2,3}\.\d{3}\.?\d{3}[\/\-]?\d{4}[-\d]{0,6}/', '', $normalized);

        // Remove pure numeric sequences (account numbers, codes, dates)
        $normalized = (string) preg_replace('/\b\d{3,}\b/', '', $normalized);

        // Remove sequences like "04/03S", dates "02/26", etc.
        $normalized = (string) preg_replace('/\d{2}\/\d{2}[a-z]?/', '', $normalized);

        // Collapse whitespace
        $normalized = (string) preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        // Take first MAX_WORDS meaningful words
        $words = array_filter(explode(' ', $normalized), static fn (string $w) => mb_strlen($w) >= 2);
        $words = array_values($words);

        return implode(' ', array_slice($words, 0, self::MAX_WORDS));
    }
}
