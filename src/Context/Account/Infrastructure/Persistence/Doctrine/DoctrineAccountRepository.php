<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Persistence\Doctrine;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Shared\Domain\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AccountRepository::class)]
final class DoctrineAccountRepository extends ServiceEntityRepository implements AccountRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function save(Account $account, bool $flush = true): void
    {
        $this->getEntityManager()->persist($account);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): Account
    {
        if (null === $account = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(Account::class, $id);
        }

        return $account;
    }
}
