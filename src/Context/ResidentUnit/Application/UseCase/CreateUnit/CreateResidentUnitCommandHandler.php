<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitVO;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventBus;
use App\Shared\Domain\InvalidArgumentException;

final readonly class CreateResidentUnitCommandHandler implements CommandHandler
{
    public function __construct(private ResidentUnitRepository $repository, private EventBus $bus) {}

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(CreateResidentUnitCommand $command): void
    {
        $idealFraction = new ResidentUnitIdealFraction($command->idealFraction());
        $idealFractionTotal = $this->repository->calculateTotalIdealFraction() + $idealFraction->value();

        if ($idealFractionTotal > 1.0) {
            throw new InvalidArgumentException('Ideal fraction must not be more than 1');
        }

        $id = new ResidentUnitId($command->id());
        $unit = new ResidentUnitVO($command->unit());

        $residentUnit = ResidentUnit::create($id, $unit, $idealFraction);

        $this->repository->save($residentUnit, true);
        $this->bus->publish(...$residentUnit->pullDomainEvents());
    }
}
