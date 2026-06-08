<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Task;
use App\Domain\Entity\TaskSkill;
use App\Domain\Repository\TaskRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Task> */
class TaskRepository extends ServiceEntityRepository implements TaskRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findOneById(int $id): ?Task
    {
        return $this->find($id);
    }

    public function findOneByIdWithLessonTasksAndSkills(int $id): ?Task
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT t, lesson, lt, ts, skill
                 FROM App\Domain\Entity\Task t
                 JOIN t.lesson lesson
                 JOIN lesson.tasks lt
                 JOIN lt.taskSkills ts
                 JOIN ts.skill skill
                 WHERE t.id = :id'
            )
            ->setParameter('id', $id)
            ->getOneOrNullResult();
    }

    public function findById(int $id): ?Task
    {
        return $this->find($id);
    }

    /** @return Task[] */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /** @return list<Task> */
    public function findAllWithCourse(): array
    {
        /* @var list<Task> */
        return $this->createQueryBuilder('t')
            ->addSelect('l', 'c')
            ->join('t.lesson', 'l')
            ->join('l.course', 'c')
            ->orderBy('c.id', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function create(Task $entity): int
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity->getId();
    }

    public function save(Task $task): void
    {
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
    }

    public function delete(Task $task): void
    {
        $this->getEntityManager()->remove($task);
        $this->getEntityManager()->flush();
    }

    public function findTaskSkill(int $taskId, int $skillId): ?TaskSkill
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT ts FROM App\Domain\Entity\TaskSkill ts
                 WHERE IDENTITY(ts.task) = :taskId
                 AND IDENTITY(ts.skill) = :skillId'
            )
            ->setParameter('taskId', $taskId)
            ->setParameter('skillId', $skillId)
            ->getOneOrNullResult();
    }

    public function deleteTaskSkill(TaskSkill $taskSkill): void
    {
        $this->getEntityManager()->remove($taskSkill);
        $this->getEntityManager()->flush();
    }
}
