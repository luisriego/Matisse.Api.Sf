<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\BankOfxMatchingContext;

use App\Shared\Application\Query;

/**
 * Returns {@see BankOfxMatchingContextDto} from DB counts (no OFX file required).
 */
final class BankOfxMatchingContextQuery implements Query {}
