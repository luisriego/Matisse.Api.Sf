<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\Service;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\Event\ExpenseWasEntered;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Gas\Domain\Event\GasReadingWasRecorded;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded;
use App\Context\Setup\Domain\OpeningSetupAggregateId;
use DateTimeImmutable;

use function end;

final class SetupStatusChecker
{
    public function __construct(
        private readonly StoredEventRepository $eventRepository,
        private readonly AccountRepository $accountRepository,
        private readonly ResidentUnitRepository $residentUnitRepository,
    ) {}

    public function status(): array
    {
        $steps = [
            'initialBalances' => $this->checkInitialBalances(),
            'gasPrice'        => $this->checkGasPrice(),
            'gasReadings'     => $this->checkGasReadings(),
            'initialExpenses' => $this->checkInitialExpenses(),
            'openingReferenceMonth' => $this->checkOpeningReferenceMonth(),
        ];

        $complete   = !in_array('pending', $steps, true);
        $currentStep = $this->resolveCurrentStep($steps);
        $message     = $complete ? null : $this->resolveMessage($steps);
        $openingReference = $this->latestOpeningReference();

        return [
            'complete'    => $complete,
            'currentStep' => $currentStep,
            'steps'       => $steps,
            'message'     => $message,
            'openingReference' => $openingReference,
        ];
    }

    public function isComplete(): bool
    {
        return $this->status()['complete'];
    }

    private function checkInitialBalances(): string
    {
        // Only active accounts count: inactive rows (e.g. abandoned onboarding retries)
        // must not block setup forever or force duplicate InitialBalanceSet events.
        $accounts = $this->accountRepository->findAllActive();

        if (empty($accounts)) {
            return 'pending';
        }

        $events = $this->eventRepository->findByEventTypesAndOccurredBetween(
            [InitialBalanceSet::eventName()],
            new DateTimeImmutable('1900-01-01'),
            null,
        );

        $accountIdsWithBalance = [];
        foreach ($events as $event) {
            $accountIdsWithBalance[$event->aggregateId()] = true;
        }

        foreach ($accounts as $account) {
            if (!isset($accountIdsWithBalance[$account->id()])) {
                return 'pending';
            }
        }

        return 'complete';
    }

    private function checkGasPrice(): string
    {
        $events = $this->eventRepository->findByEventType(GasPriceWasDefined::eventName());

        return empty($events) ? 'pending' : 'complete';
    }

    private function checkGasReadings(): string
    {
        $activeUnits = $this->residentUnitRepository->findAllActive();

        if (empty($activeUnits)) {
            return 'pending';
        }

        $events = $this->eventRepository->findByEventType(GasReadingWasRecorded::eventName());

        $unitIdsWithReading = [];
        foreach ($events as $event) {
            $payload = $event->payload();
            if (isset($payload['residentUnitId'])) {
                $unitIdsWithReading[$payload['residentUnitId']] = true;
            }
        }

        foreach ($activeUnits as $unit) {
            if (!isset($unitIdsWithReading[$unit->id()])) {
                return 'pending';
            }
        }

        return 'complete';
    }

    private function checkOpeningReferenceMonth(): string
    {
        $events = $this->eventRepository->findByEventTypesAndOccurredBetweenAndAggregateId(
            [OpeningReferenceMonthWasRecorded::eventName()],
            new DateTimeImmutable('1900-01-01'),
            null,
            OpeningSetupAggregateId::VALUE,
        );

        return empty($events) ? 'pending' : 'complete';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestOpeningReference(): ?array
    {
        $events = $this->eventRepository->findByEventTypesAndOccurredBetweenAndAggregateId(
            [OpeningReferenceMonthWasRecorded::eventName()],
            new DateTimeImmutable('1900-01-01'),
            null,
            OpeningSetupAggregateId::VALUE,
        );

        if ($events === []) {
            return null;
        }

        /** @var StoredEvent $last */
        $last = end($events);

        return array_merge(
            $last->payload(),
            ['recordedAt' => $last->occurredAt()->format(DATE_ATOM)],
        );
    }

    private function checkInitialExpenses(): string
    {
        $events = $this->eventRepository->findByEventType(ExpenseWasEntered::eventName());

        return empty($events) ? 'pending' : 'complete';
    }

    private function resolveCurrentStep(array $steps): int
    {
        $order = ['initialBalances', 'gasPrice', 'gasReadings', 'initialExpenses', 'openingReferenceMonth'];
        foreach ($order as $index => $key) {
            if ($steps[$key] === 'pending') {
                return $index + 1;
            }
        }

        return 5;
    }

    private function resolveMessage(array $steps): string
    {
        if ($steps['initialBalances'] === 'pending') {
            return 'Faltan los saldos iniciales de las cuentas. El total debe coincidir con el saldo bancario.';
        }
        if ($steps['gasPrice'] === 'pending') {
            return 'Falta configurar el precio del gas.';
        }
        if ($steps['gasReadings'] === 'pending') {
            return 'Faltan las lecturas iniciales del contador de gas de cada unidad.';
        }
        if ($steps['initialExpenses'] === 'pending') {
            return 'Falta registrar al menos un gasto del mes de corte para poder generar slips.';
        }
        if ($steps['openingReferenceMonth'] === 'pending') {
            return 'Falta registrar la previsión / parámetros del mes de referencia inicial (paso de demostrativo).';
        }

        return '';
    }
}
