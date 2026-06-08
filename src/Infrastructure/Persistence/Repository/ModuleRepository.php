<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Module;
use App\Domain\Repository\ModuleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Module> */
class ModuleRepository extends ServiceEntityRepository implements ModuleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    public function create(Module $entity): int
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity->getId();
    }

    /** @return Module[] */
    public function findWithLessons(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.lessons', 'l')
            ->addSelect('l')
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?Module
    {
        return $this->find($id);
    }

    /** @return Module[] */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    public function save(Module $module): void
    {
        $this->getEntityManager()->persist($module);
        $this->getEntityManager()->flush();
    }

    public function delete(Module $module): void
    {
        $this->getEntityManager()->remove($module);
        $this->getEntityManager()->flush();
    }
}
