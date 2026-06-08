<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\TaskExecution;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TaskExecution> */
class TaskExecutionRepository extends ServiceEntityRepository implements TaskExecutionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskExecution::class);
    }

    public function save(TaskExecution $execution): void
    {
        $this->getEntityManager()->persist($execution);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?TaskExecution
    {
        return $this->find($id);
    }

    public function findByUserAndTask(string $userId, int $taskId): ?TaskExecution
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT te FROM App\Domain\Entity\TaskExecution te
                 WHERE IDENTITY(te.user) = :userId
                 AND IDENTITY(te.task) = :taskId'
            )
            ->setParameter('userId', $userId)
            ->setParameter('taskId', $taskId)
            ->getOneOrNullResult();
    }

    /** @return TaskExecution[] */
    public function findByUser(string $userId): array
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT te FROM App\Domain\Entity\TaskExecution te
                 WHERE IDENTITY(te.user) = :userId
                 ORDER BY te.createdAt DESC'
            )
            ->setParameter('userId', $userId)
            ->getResult();
    }

    /** @return TaskExecution[] */
    public function findByTask(int $taskId): array
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT te FROM App\Domain\Entity\TaskExecution te
                 WHERE IDENTITY(te.task) = :taskId
                 ORDER BY te.createdAt DESC'
            )
            ->setParameter('taskId', $taskId)
            ->getResult();
    }

    /** @return TaskExecution[] */
    public function findByUserAndLesson(string $userId, int $lessonId): array
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT te FROM App\Domain\Entity\TaskExecution te
                 JOIN te.task t
                 WHERE IDENTITY(te.user) = :userId
                 AND IDENTITY(t.lesson) = :lessonId'
            )
            ->setParameter('userId', $userId)
            ->setParameter('lessonId', $lessonId)
            ->getResult();
    }

    /** @return TaskExecution[] */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }

    public function sumScoreByStudentAndModule(string $userId, int $moduleId): string
    {
        $result = $this->getEntityManager()
            ->createQuery(
                'SELECT COALESCE(SUM(te.score), 0)
                 FROM App\Domain\Entity\TaskExecution te
                 JOIN te.task t
                 JOIN t.lesson l
                 WHERE IDENTITY(te.user) = :userId
                 AND IDENTITY(l.module) = :moduleId'
            )
            ->setParameter('userId', $userId)
            ->setParameter('moduleId', $moduleId)
            ->getSingleScalarResult();

        return (string) $result;
    }

    public function sumScoreByStudentAndCourse(string $userId, int $courseId): string
    {
        $result = $this->getEntityManager()
            ->createQuery(
                'SELECT COALESCE(SUM(te.score), 0)
                 FROM App\Domain\Entity\TaskExecution te
                 JOIN te.task t
                 JOIN t.lesson l
                 WHERE IDENTITY(te.user) = :userId
                 AND IDENTITY(l.course) = :courseId'
            )
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->getSingleScalarResult();

        return (string) $result;
    }
}
