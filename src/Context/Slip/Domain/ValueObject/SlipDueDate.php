<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DateTimeValueObject;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;

use function sprintf;
use function trim;

class SlipDueDate extends DateTimeValueObject
{
    public function __construct(DateTime $value)
    {
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

    /**
     * @throws DateMalformedStringException
     */
    public function toDateTimeImmutable(): DateTimeImmutable
    {
        // si ya tienes un DateTimeImmutable interno, return that
        // o construye desde el valor del VO
        return new DateTimeImmutable($this->value()); // ajusta según tu implementación
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function selectDueDate(int $year, int $month): DateTime
    {
        // 1) Intentar días 6..10 y quedarse con el primero que sea día hábil (Mon-Fri)
        $firstWeekdayInWindow = null;

        for ($day = 6; $day <= 10; $day++) {
            $dt = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));

            if ($dt && (int) $dt->format('m') === $month) {
                $dow = (int) $dt->format('w'); // 0=Sunday .. 6=Saturday

                if ($dow >= 1 && $dow <= 5) { // Mon-Fri
                    $firstWeekdayInWindow = $dt;
                    break; // el primero hábil dentro de 6..10
                }
            }
        }

        if ($firstWeekdayInWindow !== null) {
            return new DateTime($firstWeekdayInWindow->format('Y-m-d H:i:s'));
        }

        // 2) Si no hay día hábil en 6..10, intentar segundo viernes si cae dentro de 6..10
        $firstOfMonth = new DateTime("{$year}-{$month}-01");
        $dayOfWeek = (int) $firstOfMonth->format('w'); // 0=Sunday
        $daysToFriday = (5 - $dayOfWeek);

        if ($daysToFriday < 0) {
            $daysToFriday += 7;
        }
        $firstFriday = (clone $firstOfMonth)->modify("+{$daysToFriday} days");
        $secondFriday = (clone $firstFriday)->modify('+7 days');
        $df = $secondFriday->format('Y-m-d');

        if ((int) $secondFriday->format('m') === $month && (int) $secondFriday->format('d') <= 10) {
            $dow = (int) $secondFriday->format('w');

            if ($dow >= 1 && $dow <= 5) { // hábil
                return new DateTime($secondFriday->format('Y-m-d H:i:s'));
            }
        }

        // 3) Fall back seguro: el primer día hábil dentro de 6..10 que exista en el mes
        for ($day = 6; $day <= 10; $day++) {
            $dt = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));

            if ($dt && (int) $dt->format('m') === $month) {
                $dow = (int) $dt->format('w');

                if ($dow >= 1 && $dow <= 5) {
                    return new DateTime($dt->format('Y-m-d H:i:s'));
                }
            }
        }

        // 4) Si todo falla, fallback al día 6 (si es válido; si no, al inicio del mes)
        $fallback = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-06', $year, $month));

        if ($fallback) {
            return new DateTime($fallback->format('Y-m-d H:i:s'));
        }

        return new DateTime("{$year}-{$month}-01");
    }
}
