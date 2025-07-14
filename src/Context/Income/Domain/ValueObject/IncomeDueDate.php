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
    public function __construct(DateTime $value)
    {
        $this->ensureIsFutureDate($value);
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
