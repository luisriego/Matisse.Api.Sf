<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Persistence\Doctrine;

use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineUserRepository extends ServiceEntityRepository implements UserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): User
    {
        if (null === $user = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(User::class, $id);
        }

        return $user;
    }

    public function findOneById(string $id): ?User
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByEmail(string $email): ?User // Changed return type to ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function hasSyndic(): bool
    {
        $result = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT 1 FROM users WHERE CAST(roles AS TEXT) LIKE :role LIMIT 1',
            ['role' => '%"' . User::ROLE_SYNDIC . '"%'],
        );

        return false !== $result;
    }

    public function findSyndics(): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT id FROM users WHERE CAST(roles AS TEXT) LIKE :role',
            ['role' => '%"' . User::ROLE_SYNDIC . '"%'],
        );

        if ([] === $ids) {
            return [];
        }

        return $this->findBy(['id' => $ids]);
    }
}
