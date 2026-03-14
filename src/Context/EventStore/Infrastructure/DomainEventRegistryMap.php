<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure;

use App\Context\Account\Domain\Bus\AccountWasDisabled;
use App\Context\Account\Domain\Bus\AccountWasEnabled;
use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Context\Expense\Domain\Bus\ExpenseWasActivated;
use App\Context\Expense\Domain\Bus\ExpenseWasCompensated;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Expense\Domain\Bus\RecurringExpenseWasCreated;
use App\Context\EventStore\Domain\DomainEventRegistry;
use App\Context\Gas\Domain\Bus\GasPriceWasDefined;
use App\Context\Gas\Domain\Bus\GasReadingWasRecorded;
use App\Context\Income\Domain\Bus\IncomeWasEntered;

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
        ];
    }

    public function getClassForEventType(string $eventType): ?string
    {
        return $this->map[$eventType] ?? null;
    }
}
