<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\GetAccountBalance;

use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Context\EventStore\Domain\DomainEventRegistry;
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
        private DomainEventRegistry $domainEventRegistry,
    ) {}

    public function __invoke(GetAccountBalanceQuery $query): int
    {
        $accountId = $query->accountId();
        $upToDate = $query->upToDate();

        $balance = 0;
        $eventsStartDate = new DateTimeImmutable('1900-01-01'); // Default start date

        // 1. Find the latest InitialBalanceSet event for the specific account
        $initialBalanceEvents = $this->eventRepository->findByEventTypesAndOccurredBetweenAndAggregateId(
            [InitialBalanceSet::eventName()],
            new DateTimeImmutable('1900-01-01'),
            $upToDate,
            $accountId,
        );

        if (!empty($initialBalanceEvents)) {
            /** @var StoredEvent $latestInitialBalanceStoredEvent */
            $latestInitialBalanceStoredEvent = end($initialBalanceEvents);
            /** @var InitialBalanceSet $initialBalanceEvent */
            $initialBalanceEvent = $latestInitialBalanceStoredEvent->toDomainEvent($this->domainEventRegistry);

            $balance = $initialBalanceEvent->amount();
            $eventsStartDate = new DateTimeImmutable($initialBalanceEvent->date());
        }

        // 2. Fetch all transaction events (expenses and incomes) in the date range
        $transactionEvents = $this->eventRepository->findByEventTypesAndOccurredBetween(
            [
                ExpenseWasEntered::eventName(),
                IncomeWasEntered::eventName(),
            ],
            $eventsStartDate,
            $upToDate,
        );

        // 3. Filter events by accountId in PHP
        foreach ($transactionEvents as $storedEvent) {
            $domainEvent = $storedEvent->toDomainEvent($this->domainEventRegistry);
            $primitives = $domainEvent->toPrimitives();

            // Skip if the event is not for the requested account
            if (!isset($primitives['accountId']) || $primitives['accountId'] !== $accountId) {
                continue;
            }

            // Skip if the event's due date is outside the upToDate limit
            $eventDueDate = new DateTimeImmutable($primitives['dueDate']);

            if ($upToDate !== null && $eventDueDate > $upToDate) {
                continue;
            }

            if ($domainEvent instanceof ExpenseWasEntered) {
                $balance -= $primitives['amount'];
            } elseif ($domainEvent instanceof IncomeWasEntered) {
                $balance += $primitives['amount'];
            }
        }

        return $balance;
    }
}
