<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\AggStudentCourse;
use App\Domain\Entity\Course;
use App\Domain\Entity\User;
use App\Domain\Repository\AggStudentCourseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AggStudentCourse> */
class AggStudentCourseRepository extends ServiceEntityRepository implements AggStudentCourseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AggStudentCourse::class);
    }

    public function upsert(string $studentId, int $courseId, float $totalScore): void
    {
        $this->doUpsert($studentId, $courseId, $totalScore, false);
    }

    private function doUpsert(string $studentId, int $courseId, float $totalScore, bool $isRetry): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $agg = $em->createQuery(
                'SELECT a FROM App\Domain\Entity\AggStudentCourse a
                 WHERE IDENTITY(a.student) = :studentId
                 AND IDENTITY(a.course) = :courseId'
            )
                ->setParameter('studentId', $studentId)
                ->setParameter('courseId', $courseId)
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if ($agg instanceof AggStudentCourse) {
                $agg->setTotalScore($totalScore);
            } else {
                $student = $em->getReference(User::class, $studentId);
                $course = $em->getReference(Course::class, $courseId);
                $em->persist(new AggStudentCourse($student, $course, $totalScore));
            }

            $em->flush();
            $em->commit();
        } catch (UniqueConstraintViolationException $e) {
            $em->rollback();
            if ($isRetry) {
                throw $e;
            }
            $this->doUpsert($studentId, $courseId, $totalScore, true);
        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }
    }

    public function findByStudentAndCourse(string $studentId, int $courseId): ?AggStudentCourse
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT a FROM App\Domain\Entity\AggStudentCourse a
                 WHERE IDENTITY(a.student) = :studentId
                 AND IDENTITY(a.course) = :courseId'
            )
            ->setParameter('studentId', $studentId)
            ->setParameter('courseId', $courseId)
            ->getOneOrNullResult();
    }
}
