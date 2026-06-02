<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain\ValueObject;

use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Shared\Domain\Exception\DueDateMustBeInTheFutureException;
use DateTime;
use PHPUnit\Framework\TestCase;

final class IncomeDueDateTest extends TestCase
{
    public function testDefaultConstructorRejectsPastDates(): void
    {
        $this->expectException(DueDateMustBeInTheFutureException::class);

        new IncomeDueDate(new DateTime('-1 day'));
    }

    public function testDefaultConstructorAcceptsTodayAndFutureDates(): void
    {
        new IncomeDueDate(new DateTime('today'));
        $future = new IncomeDueDate(new DateTime('+1 day'));

        self::assertTrue($future->isInFuture());
    }

    public function testFromBankCreditAcceptsPastDatesWithoutException(): void
    {
        $past = IncomeDueDate::fromBankCredit(new DateTime('-30 days'));

        self::assertTrue($past->isInPast());
    }

    public function testFromBankCreditStringAcceptsPastDates(): void
    {
        $past = IncomeDueDate::fromBankCreditString('2020-01-15');

        self::assertTrue($past->isInPast());
    }
}
