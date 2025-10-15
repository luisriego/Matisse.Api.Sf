<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\GetIncomesByMonth;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Shared\Application\QueryHandler;
use DateMalformedStringException;
use DateTimeImmutable;

use function array_map;
use function sprintf;

final readonly class GetIncomesByMonthQueryHandler implements QueryHandler
{
    public function __construct(private StoredEventRepository $eventRepository) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(GetIncomesByMonthQuery $query): array
    {
        $year = $query->year();
        $month = $query->month();

        $startDate = new DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $year, $month));
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        $events = $this->eventRepository->findByEventNamesAndOccurredBetween(
            [IncomeWasEntered::eventName()],
            $startDate,
            $endDate,
        );

        return array_map(function (StoredEvent $event) {
            $domainEvent = $event->toDomainEvent();

            return $domainEvent->toPrimitives();
        }, $events);
    }
}
