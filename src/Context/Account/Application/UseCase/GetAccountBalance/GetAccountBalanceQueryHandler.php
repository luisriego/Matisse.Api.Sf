<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\GetAccountBalance;

use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Shared\Application\QueryHandler;
use DateTimeImmutable;

use function end;

readonly class GetAccountBalanceQueryHandler implements QueryHandler
{
    public function __construct(
        private StoredEventRepository $eventRepository,
    ) {}

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(GetAccountBalanceQuery $query): int
    {
        $accountId = $query->accountId();
        $upToDate = $query->upToDate();

        $balance = 0;
        $eventsStartDate = new DateTimeImmutable('1900-01-01'); // Default start date

        // 1. Find the latest InitialBalanceSet event up to the query's upToDate
        $initialBalanceEvents = $this->eventRepository->findByEventNamesAndOccurredBetweenAndAggregateId(
            [InitialBalanceSet::eventName()],
            new DateTimeImmutable('1900-01-01'),
            $upToDate,
            $accountId,
        );

        if (!empty($initialBalanceEvents)) {
            /** @var StoredEvent $latestInitialBalanceStoredEvent */
            $latestInitialBalanceStoredEvent = end($initialBalanceEvents); // Already sorted by occurredAt ASC
            /** @var InitialBalanceSet $initialBalanceEvent */
            $initialBalanceEvent = $latestInitialBalanceStoredEvent->toDomainEvent();

            $balance = $initialBalanceEvent->amount();
            $eventsStartDate = new DateTimeImmutable($initialBalanceEvent->date());
        }

        // 2. Fetch all transaction events (expenses and incomes) after the initial balance date
        $transactionEvents = $this->eventRepository->findByEventNamesAndOccurredBetweenAndAggregateId(
            [
                ExpenseWasEntered::eventName(),
                IncomeWasEntered::eventName(),
            ],
            $eventsStartDate,
            $upToDate,
            $accountId,
        );

        // @var StoredEvent $event
        foreach ($transactionEvents as $storedEvent) {
            $domainEvent = $storedEvent->toDomainEvent();

            // Ensure the event's dueDate is also within the upToDate limit
            // This is important because findByEventNamesAndOccurredBetween filters by occurredAt, not dueDate
            $eventDueDate = new DateTimeImmutable($domainEvent->toPrimitives()['dueDate']);

            if ($eventDueDate > $upToDate) {
                continue;
            }

            if ($domainEvent instanceof ExpenseWasEntered) {
                $balance -= $domainEvent->toPrimitives()['amount'];
            } elseif ($domainEvent instanceof IncomeWasEntered) {
                $balance += $domainEvent->toPrimitives()['amount'];
            }
        }

        return $balance;
    }
}
