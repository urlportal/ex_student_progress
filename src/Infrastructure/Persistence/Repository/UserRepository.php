<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower($email)]);
    }

    public function findById(string $id): ?User
    {
        return $this->find($id);
    }

    /** @return User[] */
    public function findAll(): array
    {
        return parent::findAll();
    }

    /** @return list<User> */
    public function findByRole(UserRole $role): array
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        /* @var list<User> */
        return $this->getEntityManager()
            ->createNativeQuery(
                'SELECT u.* FROM "user" u WHERE CAST(u.roles AS TEXT) LIKE :role',
                $rsm
            )
            ->setParameter('role', '%"'.$role->value.'"%')
            ->getResult();
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}
