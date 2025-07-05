<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class ExpenseId extends Uuid {}
