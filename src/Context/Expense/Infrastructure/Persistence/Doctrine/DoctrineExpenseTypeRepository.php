<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Persistence\Doctrine;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineExpenseTypeRepository extends ServiceEntityRepository implements ExpenseTypeRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseType::class);
    }

    public function save(ExpenseType $type, bool $flush = true): void
    {
        $this->getEntityManager()->persist($type);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): ExpenseType
    {
        if (null === $type = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(ExpenseType::class, $id);
        }

        return $type;
    }

    public function findOneByCodeOrFail(string $code): ExpenseType
    {
        if (null === $type = $this->findOneBy(['code' => $code])) {
            throw ResourceNotFoundException::createFromClassAndId(ExpenseType::class, $code);
        }

        return $type;
    }
}
