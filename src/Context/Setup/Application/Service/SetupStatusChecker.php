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
use App\Context\Setup\Domain\Event\SetupWasCompleted;
use App\Context\Setup\Domain\OpeningSetupAggregateId;
use DateTimeImmutable;

final class SetupStatusChecker
{
    /**
     * Steps that must be complete before the app is operational (OFX import, slips, etc.).
     * initialExpenses is part of the wizard but is NOT a blocker: expenses can be entered
     * later in normal operation.
     */
    private const CORE_STEP_KEYS = [
        'initialBalances',
        'gasPrice',
        'gasReadings',
    ];

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

        $coreComplete      = $this->areCoreStepsComplete($steps);
        $fullyOnboarded    = $coreComplete && ($steps['openingReferenceMonth'] === 'complete');
        $complete          = $coreComplete;
        $currentStep       = $this->resolveCurrentStep($steps);
        $message           = $this->resolveStatusMessage($steps, $coreComplete);
        $openingReference = $this->latestOpeningReference();

        return [
            'setupFinalized'     => $this->isSetupFinalized(),
            'complete'           => $complete,
            'fullyOnboarded'     => $fullyOnboarded,
            'currentStep'        => $currentStep,
            'steps'              => $steps,
            'message'            => $message,
            'openingReference'   => $openingReference,
        ];
    }

    public function isComplete(): bool
    {
        return $this->status()['complete'];
    }

    public function isSetupFinalized(): bool
    {
        $events = $this->eventRepository->findByEventTypesAndOccurredBetweenAndAggregateId(
            [SetupWasCompleted::eventName()],
            new DateTimeImmutable('1900-01-01'),
            null,
            OpeningSetupAggregateId::VALUE,
        );

        return $events !== [];
    }

    /**
     * @param array<string, string> $steps
     */
    private function areCoreStepsComplete(array $steps): bool
    {
        foreach (self::CORE_STEP_KEYS as $key) {
            if ($steps[$key] === 'pending') {
                return false;
            }
        }

        return true;
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

        // occurred_at is second-precision in persistence: tie-break by referenceMonth (YYYY-MM sorts chronologically).
        usort(
            $events,
            static function (StoredEvent $a, StoredEvent $b): int {
                $byTime = $a->occurredAt() <=> $b->occurredAt();
                if (0 !== $byTime) {
                    return $byTime;
                }

                return ($a->payload()['referenceMonth'] ?? '') <=> ($b->payload()['referenceMonth'] ?? '');
            },
        );

        /** @var StoredEvent $last */
        $last = $events[\array_key_last($events)];

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
        $order = [...self::CORE_STEP_KEYS, 'openingReferenceMonth'];
        foreach ($order as $index => $key) {
            if ($steps[$key] === 'pending') {
                return $index + 1;
            }
        }

        return 5;
    }

    /**
     * @param array<string, string> $steps
     */
    private function resolveStatusMessage(array $steps, bool $coreComplete): ?string
    {
        if (!$coreComplete) {
            return $this->resolveCoreBlockingMessage($steps);
        }

        if ($steps['openingReferenceMonth'] === 'pending') {
            return 'Configure a previsão / demonstrativo do mês de referência quando possível; já pode usar importação OFX e o resto da aplicação.';
        }

        return null;
    }

    /**
     * @param array<string, string> $steps
     */
    private function resolveCoreBlockingMessage(array $steps): string
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

        return '';
    }
}
