<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Persistence\Doctrine;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
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

            // Lógica de inclusión:
            // - Si es mensual, siempre se incluye.
            // - Si NO es mensual, se incluye SOLO si el array monthsOfYear no es null
            //   y contiene el número del mes actual.
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
}
