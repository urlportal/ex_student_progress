<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Course;
use App\Domain\Repository\CourseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Course> */
class CourseRepository extends ServiceEntityRepository implements CourseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function create(Course $entity): int
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity->getId();
    }

    /** @return Course[] */
    public function searchByTitle(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.title LIKE :searchQuery')
            ->setParameter('searchQuery', '%'.$query.'%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Course[] */
    public function findWithModules(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.modules', 'm')
            ->addSelect('m')
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?Course
    {
        return $this->find($id);
    }

    public function findAll(): array
    {
        return $this->findBy([]);
    }

    public function save(Course $course): void
    {
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
    }

    public function delete(Course $course): void
    {
        $this->getEntityManager()->remove($course);
        $this->getEntityManager()->flush();
    }

    public function findByTitle(string $title): ?Course
    {
        return $this->findOneBy(['title' => $title]);
    }
}
