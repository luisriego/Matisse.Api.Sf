<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Persistence\Doctrine;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

use function in_array;

class DoctrineRecurringExpenseRepository extends ServiceEntityRepository implements RecurringExpenseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringExpense::class);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(RecurringExpense $recurringExpense, bool $flush = true): void
    {
        $this->getEntityManager()->persist($recurringExpense);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RecurringExpense $recurringExpense, bool $flush = true): void
    {
        $this->getEntityManager()->remove($recurringExpense);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws Exception
     */
    public function findOneByIdOrFail(string $id): RecurringExpense
    {
        if (null === $recurringExpense = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(RecurringExpense::class, $id);
        }

        return $recurringExpense;
    }

    public function findForThisMonth(int $month): array
    {
        $allActiveRecurring = $this->findAllActives();

        $activeForThisMonth = [];

        foreach ($allActiveRecurring as $recurringExpense) {
            $monthsOfYear = $recurringExpense->monthsOfYear();

            if ($monthsOfYear !== null && in_array($month, $monthsOfYear, true)) {
                $activeForThisMonth[] = $recurringExpense;
            }
        }

        return $activeForThisMonth;
    }

    private function findAllActives(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('r.dueDay', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findActiveForDateRange(DateRange $dateRange): array
    {
        $start = $dateRange->startDate();
        $end   = $dateRange->endDate();

        $potentiallyActive = $this->createQueryBuilder('r')
            ->where('r.isActive = :isActive')
            ->andWhere('r.startDate <= :endDate')
            ->andWhere('r.endDate IS NULL OR r.endDate >= :startDate')
            ->setParameter('isActive', true)
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end)
            ->orderBy('r.dueDay', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        $monthOfRange = (int) $start->format('m');
        $daysInMonth = (int) $start->format('t');

        foreach ($potentiallyActive as $recurring) {
            $months = $recurring->monthsOfYear();

            if (null === $months || in_array($monthOfRange, $months, true)) {
                if ($recurring->dueDay() >= 1 && $recurring->dueDay() <= $daysInMonth) {
                    $result[] = $recurring;
                }
            }
        }

        return $result;
    }
}
