<?php

declare(strict_types=1);

namespace App\Context\Income\Domain\ValueObject;

use App\Shared\Domain\Exception\DueDateMustBeInTheFutureException;
use App\Shared\Domain\ValueObject\DateTimeValueObject;
use DateMalformedStringException;
use DateTime;

use function trim;

class IncomeDueDate extends DateTimeValueObject
{
    public function __construct(DateTime $value, bool $allowPast = false)
    {
        if (!$allowPast) {
            $this->ensureIsFutureDate($value);
        }
        parent::__construct($value);
    }

    public static function fromDateTime(DateTime $dueDate): self
    {
        return new self($dueDate);
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function from(?string $date = null): self
    {
        if ($date === null || trim($date) === '') {
            return new self(new DateTime());
        }

        return new self(new DateTime($date));
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromString(string $date): self
    {
        return new self(new DateTime($date));
    }

    /**
     * Factory intended for incomes originated by bank statement CREDIT lines,
     * where the posted date has already occurred. Skips the future-date invariant.
     */
    public static function fromBankCredit(DateTime $postedAt): self
    {
        return new self($postedAt, allowPast: true);
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromBankCreditString(string $postedAt): self
    {
        return new self(new DateTime($postedAt), allowPast: true);
    }

    public function isInPast(): bool
    {
        return $this->value < new DateTime();
    }

    public function isInFuture(): bool
    {
        return $this->value > new DateTime();
    }

    private function ensureIsFutureDate(DateTime $date): void
    {
        if ($date < new DateTime('today')) {
            throw new DueDateMustBeInTheFutureException();
        }
    }
}
