<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\AggStudentLessonSkill;
use App\Domain\Repository\AggStudentLessonSkillRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AggStudentLessonSkill> */
class AggStudentLessonSkillRepository extends ServiceEntityRepository implements AggStudentLessonSkillRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AggStudentLessonSkill::class);
    }

    public function save(AggStudentLessonSkill $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * @param AggStudentLessonSkill[] $entities
     *
     * DBAL используется намеренно вместо EntityManager::persist/flush, чтобы обойти
     * Doctrine Unit of Work. Нативный SQL DELETE выбран потому, что альтернатива через
     * ORM (findBy -> remove -> flush) загружает все удаляемые сущности в память и UoW,
     * тогда как один DELETE-запрос по условию это делает без лишних SELECT и объектов.
     * При этом bulk-операции (DQL/native SQL DELETE) не уведомляют UoW об удалённых
     * записях, поэтому в долгоживущем consumer'е Messenger при следующем flush() ORM
     * мог бы попытаться обновить уже удалённые строки. Работа напрямую через Connection
     * исключает любое взаимодействие с UoW и гарантирует атомарность операции.
     *
     * @throws \Throwable
     */
    public function replaceByStudentAndLesson(string $studentId, int $lessonId, array $entities): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $conn->transactional(function (Connection $conn) use ($studentId, $lessonId, $entities, $now): void {
            $conn->executeStatement(
                'DELETE FROM agg_student_lesson_skill WHERE student_id = :studentId AND lesson_id = :lessonId',
                ['studentId' => $studentId, 'lessonId' => $lessonId]
            );

            foreach ($entities as $entity) {
                $conn->insert('agg_student_lesson_skill', [
                    'student_id' => $entity->getStudent()->getId(),
                    'lesson_id' => $entity->getLesson()->getId(),
                    'skill_id' => $entity->getSkill()->getId(),
                    'total_score' => $entity->getTotalScore(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    /**
     * @return AggStudentLessonSkill[]
     */
    public function findByStudentAndLesson(string $studentId, int $lessonId): array
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT a, s FROM App\Domain\Entity\AggStudentLessonSkill a
                 JOIN a.skill s
                 WHERE IDENTITY(a.student) = :studentId
                 AND IDENTITY(a.lesson) = :lessonId'
            )
            ->setParameter('studentId', $studentId)
            ->setParameter('lessonId', $lessonId)
            ->getResult();
    }
}
