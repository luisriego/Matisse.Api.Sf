<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain\ValueObject;

use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Shared\Domain\Exception\DueDateMustBeInTheFutureException;
use DateTime;
use PHPUnit\Framework\TestCase;

final class IncomeDueDateTest extends TestCase
{
    public function test_default_constructor_rejects_past_dates(): void
    {
        $this->expectException(DueDateMustBeInTheFutureException::class);

        new IncomeDueDate(new DateTime('-1 day'));
    }

    public function test_default_constructor_accepts_today_and_future_dates(): void
    {
        new IncomeDueDate(new DateTime('today'));
        $future = new IncomeDueDate(new DateTime('+1 day'));

        self::assertTrue($future->isInFuture());
    }

    public function test_from_bank_credit_accepts_past_dates_without_exception(): void
    {
        $past = IncomeDueDate::fromBankCredit(new DateTime('-30 days'));

        self::assertTrue($past->isInPast());
    }

    public function test_from_bank_credit_string_accepts_past_dates(): void
    {
        $past = IncomeDueDate::fromBankCreditString('2020-01-15');

        self::assertTrue($past->isInPast());
    }
}
