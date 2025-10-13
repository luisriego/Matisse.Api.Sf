<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\UpdateIncome;

use App\Context\Income\Domain\IncomeRepository;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Shared\Application\CommandHandler;
use DateMalformedStringException;
use DateTime;

readonly class UpdateIncomeCommandHandler implements CommandHandler
{
    public function __construct(
        private IncomeRepository $incomeRepository,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(UpdateIncomeCommand $command): void
    {
        $id = new IncomeId($command->id());
        $income = $this->incomeRepository->findOneByIdOrFail($id->value());

        if (null !== $command->dueDate()) {
            $dueDate = new IncomeDueDate(new DateTime($command->dueDate()));
            $income->updateDueDate($dueDate->toDateTime());
        }

        if (null !== $command->description()) {
            $income->updateDescription($command->description());
        }

        $this->incomeRepository->save($income, true);
    }
}
