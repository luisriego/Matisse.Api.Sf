<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

enum SlipStatus: string
{
    /** The slip has been generated and is awaiting distribution and payment. */
    case PENDING = 'pending';

    /** The slip has been distributed to recipients (neighbors) and is awaiting payment. */
    case SUBMITTED = 'submitted';

    /** The slip has been successfully paid. */
    case PAID = 'paid';

    /** The due date has passed, and the slip has not been paid. */
    case OVERDUE = 'overdue';

    /** The slip was voided and is no longer valid. */
    case CANCELLED = 'cancelled';

    public function isTerminal(): bool
    {
        return $this === self::PAID || $this === self::CANCELLED;
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::PENDING   => in_array($to, [self::SUBMITTED, self::PAID, self::CANCELLED, self::OVERDUE], true),
            self::SUBMITTED => in_array($to, [self::PAID, self::CANCELLED, self::OVERDUE], true),
            self::OVERDUE   => in_array($to, [self::PAID, self::CANCELLED], true),
            default         => false, // PAID, CANCELLED => no transitions
        };
    }
}