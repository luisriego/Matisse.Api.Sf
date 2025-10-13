<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Slip\Domain\Exception\GenerationNotAllowedYetException;
use App\Context\Slip\Domain\Exception\PastMonthGenerationRequiresConfirmationException;
use App\Context\Slip\Domain\Exception\RecreationExpiredException;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Domain\Clock;
use DateMalformedStringException;
use DateTimeImmutable;

use function sprintf;

readonly class SlipGenerationPolicy
{
    public function __construct(
        private SlipRepository $slipRepository,
        private Clock $clock,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function check(int $expenseYear, int $expenseMonth, bool $isForced = false): void
    {
        // The "force" flag is a master override for all time-based rules.
        // It's used for administrative tasks like back-filling historical data.
        if ($isForced) {
            return;
        }

        $today = $this->clock->now()->setTime(0, 0, 0);

        // The due date is for the month following the expenses.
        $expenseMonthContext = new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth));
        $dueDateContext = $expenseMonthContext->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');

        $lastDayForRecreation = (new DateTimeImmutable(sprintf('%d-%d-05', $dueYear, $dueMonth)))->setTime(23, 59, 59);

        $slipsExist = $this->slipRepository->existsForDueDateMonth($dueYear, $dueMonth);

        // --- Rule for re-creation ---
        if ($slipsExist) {
            if ($today > $lastDayForRecreation) {
                throw new RecreationExpiredException(sprintf(
                    'Re-creation of slips is not allowed after the 5th of the due date month. The deadline was %s.',
                    $lastDayForRecreation->format('Y-m-d'),
                ));
            }

            return;
        }

        // --- Rules for first-time generation ---
        $firstDayToGenerate = new DateTimeImmutable(sprintf('%d-%d-25', $expenseYear, $expenseMonth));

        if ($today < $firstDayToGenerate) {
            throw new GenerationNotAllowedYetException(sprintf(
                'First-time generation is only allowed from the 25th of the expense month. Please wait until %s.',
                $firstDayToGenerate->format('Y-m-d'),
            ));
        }

        $lastDayForFirstTime = (new DateTimeImmutable(sprintf('%d-%d-05', $dueYear, $dueMonth)))->setTime(23, 59, 59);

        if ($today > $lastDayForFirstTime) {
            // This is a special case. We are trying to generate for the first time, but we are past the deadline.
            // This usually happens when trying to generate for a past month that was skipped.
            // The system should ask for confirmation instead of failing outright.
            throw new PastMonthGenerationRequiresConfirmationException(
                'Generating slips for a past month requires confirmation. The standard deadline has passed.',
            );
        }
    }
}
