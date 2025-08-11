<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

enum SlipStatus: string
{
    /** The slip has been generated and is awaiting payment. */
    case PENDING = 'pending';

    /** The slip has been successfully paid. */
    case PAID = 'paid';

    /** The due date has passed, and the slip has not been paid. */
    case OVERDUE = 'overdue';

    /** The slip was voided and is no longer valid. */
    case CANCELLED = 'cancelled';
}
