<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Persistence\Doctrine;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\DateRange;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function sprintf;

class DoctrineExpenseRepository extends ServiceEntityRepository implements ExpenseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(Expense $expense, bool $flush = true): void
    {
        $this->getEntityManager()->persist($expense);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Expense $expense, bool $flush = true): void
    {
        $this->getEntityManager()->remove($expense);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): Expense
    {
        if (null === $expense = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(Expense::class, $id);
        }

        return $expense;
    }

    public function findAll(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('e')
            ->from(Expense::class, 'e')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByDateRange(DateRange $dateRange): array
    {
        return $this->getEntityManager()
            ->createQuery('
            SELECT e FROM App\Context\Expense\Domain\Expense e 
            WHERE e.isActive = true 
            AND e.dueDate >= :startDate 
            AND e.dueDate <= :endDate
            ORDER BY e.dueDate ASC
        ')
            ->setParameter('startDate', $dateRange->startDate())
            ->setParameter('endDate', $dateRange->endDate())
            ->getResult();
    }

    public function findInactiveByDateRange(DateRange $dateRange): array
    {
        return $this->getEntityManager()
            ->createQuery('
            SELECT e FROM App\Context\Expense\Domain\Expense e 
            WHERE e.isActive = false 
            AND e.dueDate >= :startDate 
            AND e.dueDate <= :endDate
            ORDER BY e.dueDate ASC
        ')
            ->setParameter('startDate', $dateRange->startDate())
            ->setParameter('endDate', $dateRange->endDate())
            ->getResult();
    }

    public function findByRecurringExpenseAndMonthYear(string $recurringExpenseId, int $month, int $year): ?Expense
    {
        $startDate = new DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $year, $month));
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('e')
            ->join('e.recurringExpense', 're')
            ->where('re.id = :recurringExpenseId')
            ->andWhere('e.dueDate >= :startDate')
            ->andWhere('e.dueDate <= :endDate')
            ->setParameter('recurringExpenseId', $recurringExpenseId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
