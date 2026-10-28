<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure;

use App\Context\Account\Domain\Event\AccountDetailsWereUpdated;
use App\Context\Account\Domain\Event\AccountWasDisabled;
use App\Context\Account\Domain\Event\AccountWasEnabled;
use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Context\BillingPolicy\Domain\Event\MonthlyBillingParametersWereRecorded;
use App\Context\EventStore\Domain\DomainEventRegistry;
use App\Context\Expense\Domain\Event\ExpenseWasActivated;
use App\Context\Expense\Domain\Event\ExpenseWasCompensated;
use App\Context\Expense\Domain\Event\ExpenseWasEntered;
use App\Context\Expense\Domain\Event\RecurringExpenseWasCreated;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Gas\Domain\Event\GasReadingWasRecorded;
use App\Context\Income\Domain\Event\IncomeDescriptionWasChanged;
use App\Context\Income\Domain\Event\IncomeDueDateWasChanged;
use App\Context\Income\Domain\Event\IncomeWasCategorized;
use App\Context\Income\Domain\Event\IncomeWasEntered;
use App\Context\Income\Domain\Event\IncomeWasPaid;
use App\Context\ResidentUnit\Domain\Event\ResidentUnitIdealFractionWasChanged;
use App\Context\ResidentUnit\Domain\Event\ResidentUnitRecipientsWereReplaced;
use App\Context\ResidentUnit\Domain\Event\ResidentUnitRecipientWasAppended;
use App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded;
use App\Context\Setup\Domain\Event\SetupWasCompleted;
use App\Context\Slip\Domain\Event\PeriodWasClosed;
use App\Context\Slip\Domain\Event\SlipWasCancelled;
use App\Context\Slip\Domain\Event\SlipWasImported;
use App\Context\Slip\Domain\Event\SlipWasMarkedAsOverdue;
use App\Context\Slip\Domain\Event\SlipWasPaid;
use App\Context\Slip\Domain\Event\SlipWasSubmitted;
use App\Context\User\Domain\Event\UserWasRegistered;

final class DomainEventRegistryMap implements DomainEventRegistry
{
    /** @var array<string, class-string> */
    private array $map;

    public function __construct()
    {
        $this->map = [
            IncomeWasEntered::eventName()                        => IncomeWasEntered::class,
            ExpenseWasEntered::eventName()                       => ExpenseWasEntered::class,
            ExpenseWasActivated::eventName()                     => ExpenseWasActivated::class,
            ExpenseWasCompensated::eventName()                   => ExpenseWasCompensated::class,
            RecurringExpenseWasCreated::eventName()              => RecurringExpenseWasCreated::class,
            InitialBalanceSet::eventName()                       => InitialBalanceSet::class,
            AccountWasDisabled::eventName()                      => AccountWasDisabled::class,
            AccountWasEnabled::eventName()                       => AccountWasEnabled::class,
            AccountDetailsWereUpdated::eventName()               => AccountDetailsWereUpdated::class,
            GasPriceWasDefined::eventName()                      => GasPriceWasDefined::class,
            GasReadingWasRecorded::eventName()                   => GasReadingWasRecorded::class,
            IncomeDescriptionWasChanged::eventName()             => IncomeDescriptionWasChanged::class,
            IncomeDueDateWasChanged::eventName()                 => IncomeDueDateWasChanged::class,
            IncomeWasCategorized::eventName()                    => IncomeWasCategorized::class,
            IncomeWasPaid::eventName()                           => IncomeWasPaid::class,
            ResidentUnitIdealFractionWasChanged::eventName()     => ResidentUnitIdealFractionWasChanged::class,
            ResidentUnitRecipientWasAppended::eventName()        => ResidentUnitRecipientWasAppended::class,
            ResidentUnitRecipientsWereReplaced::eventName()      => ResidentUnitRecipientsWereReplaced::class,
            MonthlyBillingParametersWereRecorded::eventName()    => MonthlyBillingParametersWereRecorded::class,
            OpeningReferenceMonthWasRecorded::eventName()        => OpeningReferenceMonthWasRecorded::class,
            SetupWasCompleted::eventName()                       => SetupWasCompleted::class,
            PeriodWasClosed::eventName()                         => PeriodWasClosed::class,
            SlipWasSubmitted::eventName()                        => SlipWasSubmitted::class,
            SlipWasPaid::eventName()                             => SlipWasPaid::class,
            SlipWasMarkedAsOverdue::eventName()                  => SlipWasMarkedAsOverdue::class,
            SlipWasCancelled::eventName()                        => SlipWasCancelled::class,
            SlipWasImported::eventName()                         => SlipWasImported::class,
            UserWasRegistered::eventName()                       => UserWasRegistered::class,
        ];
    }

    public function getClassForEventType(string $eventType): ?string
    {
        return $this->map[$eventType] ?? null;
    }
}
