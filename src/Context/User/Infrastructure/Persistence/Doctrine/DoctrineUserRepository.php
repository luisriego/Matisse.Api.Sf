<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Persistence\Doctrine;

use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array @orderBy = null, $limit = null, $offset = null)
 */
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

    public function findByEmail(string $email): ?User // Changed return type to ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
}
