<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Lesson;
use App\Domain\Repository\LessonRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Lesson> */
class LessonRepository extends ServiceEntityRepository implements LessonRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function create(Lesson $entity): int
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity->getId();
    }

    public function findById(int $id): ?Lesson
    {
        return $this->find($id);
    }

    /** @return Lesson[] */
    public function findWithTasks(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.tasks', 't')
            ->addSelect('t')
            ->getQuery()
            ->getResult();
    }

    /** @return Lesson[] */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    public function save(Lesson $lesson): void
    {
        $this->getEntityManager()->persist($lesson);
        $this->getEntityManager()->flush();
    }

    public function delete(Lesson $lesson): void
    {
        $this->getEntityManager()->remove($lesson);
        $this->getEntityManager()->flush();
    }
}
