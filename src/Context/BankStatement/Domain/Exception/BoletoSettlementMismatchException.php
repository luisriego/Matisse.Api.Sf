<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain\Exception;

use RuntimeException;

use function sprintf;

/**
 * Thrown when the sum of OFX "boleto_settlement" CREDIT lines does NOT match
 * the expected total, and that expected total is positive (sum of Slip amounts in the month).
 *
 * When the expected total is zero (no slips / greenfield), the handler accepts the bank sum instead.
 *
 * Carries structured data so the API can return a 422 with a detailed body.
 */
final class BoletoSettlementMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $expectedCents,
        public readonly int $receivedCents,
        public readonly int $settlementYear,
        public readonly int $settlementMonth,
    ) {
        parent::__construct(sprintf(
            'Boleto settlement mismatch for %04d-%02d: expected %d cents, received %d cents (diff %+d).',
            $settlementYear,
            $settlementMonth,
            $expectedCents,
            $receivedCents,
            $receivedCents - $expectedCents,
        ));
    }

    public function diffCents(): int
    {
        return $this->receivedCents - $this->expectedCents;
    }

    public function toPayload(): array
    {
        return [
            'settlementMonth' => sprintf('%04d-%02d', $this->settlementYear, $this->settlementMonth),
            'expectedCents'   => $this->expectedCents,
            'receivedCents'   => $this->receivedCents,
            'diffCents'       => $this->diffCents(),
        ];
    }
}
