<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

/** A single CREDIT line from the OFX, sent by the frontend for income verification. */
final readonly class CreditLineDto
{
    public function __construct(
        public readonly string $importLineKey,
        public readonly int    $amountInCents,
        public readonly string $memo,
    ) {}
}
