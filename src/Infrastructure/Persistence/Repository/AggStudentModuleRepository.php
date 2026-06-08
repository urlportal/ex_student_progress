<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\AggStudentModule;
use App\Domain\Entity\Module;
use App\Domain\Entity\User;
use App\Domain\Repository\AggStudentModuleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AggStudentModule> */
class AggStudentModuleRepository extends ServiceEntityRepository implements AggStudentModuleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AggStudentModule::class);
    }

    public function upsert(string $studentId, int $moduleId, float $totalScore): void
    {
        $this->doUpsert($studentId, $moduleId, $totalScore, false);
    }

    private function doUpsert(string $studentId, int $moduleId, float $totalScore, bool $isRetry): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $agg = $em->createQuery(
                'SELECT a FROM App\Domain\Entity\AggStudentModule a
                 WHERE IDENTITY(a.student) = :studentId
                 AND IDENTITY(a.module) = :moduleId'
            )
                ->setParameter('studentId', $studentId)
                ->setParameter('moduleId', $moduleId)
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if ($agg instanceof AggStudentModule) {
                $agg->setTotalScore($totalScore);
            } else {
                $student = $em->getReference(User::class, $studentId);
                $module = $em->getReference(Module::class, $moduleId);
                $em->persist(new AggStudentModule($student, $module, $totalScore));
            }

            $em->flush();
            $em->commit();
        } catch (UniqueConstraintViolationException $e) {
            $em->rollback();
            if ($isRetry) {
                throw $e;
            }
            $this->doUpsert($studentId, $moduleId, $totalScore, true);
        } catch (\Throwable $e) {
            $em->rollback();
            throw $e;
        }
    }

    public function findByStudentAndModule(string $studentId, int $moduleId): ?AggStudentModule
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT a FROM App\Domain\Entity\AggStudentModule a
                 WHERE IDENTITY(a.student) = :studentId
                 AND IDENTITY(a.module) = :moduleId'
            )
            ->setParameter('studentId', $studentId)
            ->setParameter('moduleId', $moduleId)
            ->getOneOrNullResult();
    }
}
