<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Guard;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommand;
use App\Context\Expense\Domain\Expense;
use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommand;
use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Domain\PeriodClosureRepository;
use DateTime;

readonly class PeriodClosureExpenseIncomeGuard
{
    public function __construct(
        private PeriodClosureRepository $repository,
    ) {}

    /**
     * @param EnterExpenseCommand|EnterIncomeCommand|UpdateExpenseCommand|UpdateIncomeCommand|Expense $subject
     */
    public function __invoke(object $subject): void
    {
        $dueDate = match (true) {
            $subject instanceof EnterExpenseCommand => $subject->dueDate(),
            $subject instanceof EnterIncomeCommand => $subject->dueDate(),
            $subject instanceof UpdateExpenseCommand => $subject->dueDate(),
            $subject instanceof UpdateIncomeCommand => $subject->dueDate(),
            $subject instanceof Expense => $subject->dueDate()->format('Y-m-d'),
            default => null,
        };

        if (null === $dueDate) {
            return;
        }

        $dateTime = new DateTime($dueDate);
        $year = (int) $dateTime->format('Y');
        $month = (int) $dateTime->format('m');

        if ($this->repository->existsForMonth($year, $month)) {
            throw PeriodAlreadyClosedException::forMonth($year, $month);
        }
    }
}
