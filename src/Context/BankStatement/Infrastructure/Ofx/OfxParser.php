<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Ofx;

use App\Context\BankStatement\Domain\BankTransaction;
use App\Context\BankStatement\Domain\ParsedBankStatement;
use App\Shared\Domain\ValueObject\DateTimeValueObject;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

use function abs;
use function array_filter;
use function array_values;
use function preg_match;
use function preg_replace;
use function round;
use function str_replace;
use function trim;

/**
 * Parses OFX 102 (SGML-style) bank statements.
 * Handles the flat-tag format exported by Brazilian banks (Itaú, Bradesco, etc.).
 */
final class OfxParser
{
    /**
     * @throws DateMalformedStringException
     */
    public function parse(string $ofxContent): ParsedBankStatement
    {
        $body = $this->extractBody($ofxContent);

        $bankId    = $this->extractTag($body, 'BANKID') ?? '';
        $accountId = $this->extractTag($body, 'ACCTID') ?? '';
        $currency  = $this->extractTag($body, 'CURDEF') ?? 'BRL';

        $dtStart = $this->parseOfxDate($this->extractTag($body, 'DTSTART') ?? '');
        $dtEnd   = $this->parseOfxDate($this->extractTag($body, 'DTEND') ?? '');

        $transactions = $this->parseTransactions($body, $accountId);

        $ledgerBalance     = null;
        $ledgerBalanceDate = null;

        $balamt = $this->extractTag($body, 'BALAMT');
        $dtasof = $this->extractTag($body, 'DTASOF');

        if ($balamt !== null) {
            $ledgerBalance     = (int) round((float) str_replace(',', '.', $balamt) * 100);
            $ledgerBalanceDate = $dtasof ? $this->parseOfxDate($dtasof) : null;
        }

        return new ParsedBankStatement(
            bankId: $bankId,
            accountId: $accountId,
            currency: $currency,
            periodStart: $dtStart,
            periodEnd: $dtEnd,
            transactions: $transactions,
            ledgerBalanceInCents: $ledgerBalance,
            ledgerBalanceDate: $ledgerBalanceDate,
        );
    }

    // --- private helpers ---

    private function extractBody(string $content): string
    {
        // Strip OFX headers (lines before <OFX>)
        $pos = strpos($content, '<OFX>');

        if ($pos === false) {
            throw new RuntimeException('Invalid OFX file: missing <OFX> root element.');
        }

        return substr($content, $pos);
    }

    private function extractTag(string $content, string $tag): ?string
    {
        if (preg_match('/<' . $tag . '>([^\r\n<]+)/', $content, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * @return BankTransaction[]
     * @throws DateMalformedStringException
     */
    private function parseTransactions(string $body, string $accountId): array
    {
        // Extract each <STMTTRN>...</STMTTRN> block
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $body, $matches);

        $transactions = [];

        foreach ($matches[1] as $block) {
            $type   = $this->extractTag($block, 'TRNTYPE') ?? 'DEBIT';
            $dtRaw  = $this->extractTag($block, 'DTPOSTED') ?? '';
            $amount = $this->extractTag($block, 'TRNAMT') ?? '0';
            $importLineKey = $this->extractTag($block, 'FITID') ?? uniqid('ofx_', true);
            $memo   = $this->extractTag($block, 'MEMO') ?? '';

            $amountFloat  = (float) str_replace(',', '.', $amount);
            $amountCents  = (int) round(abs($amountFloat) * 100);
            $signedCents  = $type === 'CREDIT' ? $amountCents : -$amountCents;

            $transactions[] = new BankTransaction(
                importLineKey: $importLineKey,
                bankAccountId: $accountId,
                type: $type,
                amountInCents: $signedCents,
                postedAt: $this->parseOfxDate($dtRaw),
                memo: $this->normalizeMemo($memo),
            );
        }

        return array_values(array_filter($transactions));
    }

    /**
     * Parse OFX date format: 20260302100000[-03:EST]; only the calendar day is kept.
     *
     * @throws DateMalformedStringException
     */
    private function parseOfxDate(string $raw): DateTimeValueObject
    {
        // Strip timezone suffix [...]
        $clean = preg_replace('/\[.*\]/', '', $raw);
        $clean = trim($clean ?? $raw);

        $immutable = match (true) {
            strlen($clean) >= 14 => DateTimeImmutable::createFromFormat('YmdHis', substr($clean, 0, 14), new DateTimeZone('UTC')),
            strlen($clean) >= 8  => DateTimeImmutable::createFromFormat('Ymd', substr($clean, 0, 8), new DateTimeZone('UTC')),
            default              => new DateTimeImmutable('now', new DateTimeZone('UTC')),
        };

        return new DateTimeValueObject($immutable);
    }

    private function normalizeMemo(string $memo): string
    {
        // Collapse multiple spaces, trim, uppercase
        return trim((string) preg_replace('/\s+/', ' ', $memo));
    }
}
