<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\EnterIncome;

use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use DateMalformedStringException;
use DateTime;

readonly class EnterIncomeCommandHandler implements CommandHandler
{
    public function __construct(
        private IncomeRepository $incomeRepository,
        private IncomeTypeRepository $incomeTypeRepository,
        private ResidentUnitRepository $residentUnitRepository,
        private EventBus $bus,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(EnterIncomeCommand $command): void
    {
        $id = new IncomeId($command->id());
        $amount = new IncomeAmount($command->amount());
        $residentUnit = $this->residentUnitRepository->findOneByIdOrFail($command->residentUnitId());
        $type = $this->incomeTypeRepository->findOneByIdOrFail($command->type());
        $dueDate = new IncomeDueDate(new DateTime($command->dueDate()));
        $descriptionValue = $command->description();

        // Modificar la llamada a Income::create() para incluir los nuevos argumentos
        $income = Income::create(
            $id,
            $amount,
            $residentUnit,
            $type,
            $dueDate,
            0, // mainAccountAmount por defecto
            0, // gasAmount por defecto
            0, // reserveFundAmount por defecto
            0, // constructionFundAmount por defecto
            $descriptionValue,
        );

        if ($income->hasDomainEvents()) {
            $this->incomeRepository->save($income, false);
            $this->bus->publish(...$income->pullDomainEvents());
        }

        $this->incomeRepository->save($income, true);
    }
}
