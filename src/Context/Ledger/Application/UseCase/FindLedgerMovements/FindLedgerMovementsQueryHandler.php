<?php

declare(strict_types=1);

namespace App\Context\Ledger\Application\UseCase\FindLedgerMovements;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Application\QueryHandler;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

use function sprintf;
use function usort;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class FindLedgerMovementsQueryHandler implements QueryHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private IncomeRepository $incomeRepository,
        private SerializerInterface $serializer,
    ) {}

    /**
     * @return array{month: string, movements: list<array<string, mixed>>}
     *
     * @throws DateMalformedStringException
     */
    public function __invoke(FindLedgerMovementsQuery $query): array
    {
        $range = DateRange::fromMonth($query->year(), $query->month());
        $expenses = $this->expenseRepository->findActiveByDateRange($range);
        $incomes   = $this->incomeRepository->findActiveByDateRange($range);

        $accountId = $query->accountId();

        $movements = [];

        foreach ($expenses as $expense) {
            if ($accountId !== null && $expense->account()?->id() !== $accountId) {
                continue;
            }
            $movements[] = [
                'kind'        => 'expense',
                'occurredOn' => $expense->dueDate()->format('Y-m-d'),
                'id'          => $expense->id(),
                'payload'     => $this->serializer->normalize($expense),
            ];
        }

        foreach ($incomes as $income) {
            if ($accountId !== null && $income->accountId() !== $accountId) {
                continue;
            }
            $payload = $income->toArray();

            $movements[] = [
                'kind'        => 'income',
                'occurredOn' => $income->dueDate()->format('Y-m-d'),
                'id'          => $income->id(),
                'payload'     => $payload,
            ];
        }

        usort(
            $movements,
            static function (array $a, array $b): int {
                $byDate = strcmp((string) $a['occurredOn'], (string) $b['occurredOn']);
                if ($byDate !== 0) {
                    return $byDate;
                }

                return strcmp((string) $a['id'], (string) $b['id']);
            },
        );

        return [
            'month'     => sprintf('%04d-%02d', $query->year(), $query->month()),
            'movements' => $movements,
        ];
    }
}
