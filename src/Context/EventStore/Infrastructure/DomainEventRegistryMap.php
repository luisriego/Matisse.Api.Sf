<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure;

use App\Context\Account\Domain\Event\AccountWasDisabled;
use App\Context\Account\Domain\Event\AccountWasEnabled;
use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Context\EventStore\Domain\DomainEventRegistry;
use App\Context\Expense\Domain\Event\ExpenseWasActivated;
use App\Context\Expense\Domain\Event\ExpenseWasCompensated;
use App\Context\Expense\Domain\Event\ExpenseWasEntered;
use App\Context\Expense\Domain\Event\RecurringExpenseWasCreated;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Gas\Domain\Event\GasReadingWasRecorded;
use App\Context\Income\Domain\Event\IncomeWasEntered;
use App\Context\BillingPolicy\Domain\Event\MonthlyBillingParametersWereRecorded;
use App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded;
use App\Context\Setup\Domain\Event\SetupWasCompleted;

final class DomainEventRegistryMap implements DomainEventRegistry
{
    /** @var array<string, class-string> */
    private array $map;

    public function __construct()
    {
        $this->map = [
            IncomeWasEntered::eventName()           => IncomeWasEntered::class,
            ExpenseWasEntered::eventName()          => ExpenseWasEntered::class,
            ExpenseWasActivated::eventName()        => ExpenseWasActivated::class,
            ExpenseWasCompensated::eventName()      => ExpenseWasCompensated::class,
            RecurringExpenseWasCreated::eventName() => RecurringExpenseWasCreated::class,
            InitialBalanceSet::eventName()          => InitialBalanceSet::class,
            AccountWasDisabled::eventName()         => AccountWasDisabled::class,
            AccountWasEnabled::eventName()          => AccountWasEnabled::class,
            GasPriceWasDefined::eventName()         => GasPriceWasDefined::class,
            GasReadingWasRecorded::eventName()      => GasReadingWasRecorded::class,
            MonthlyBillingParametersWereRecorded::eventName() => MonthlyBillingParametersWereRecorded::class,
            OpeningReferenceMonthWasRecorded::eventName() => OpeningReferenceMonthWasRecorded::class,
            SetupWasCompleted::eventName() => SetupWasCompleted::class,
        ];
    }

    public function getClassForEventType(string $eventType): ?string
    {
        return $this->map[$eventType] ?? null;
    }
}
