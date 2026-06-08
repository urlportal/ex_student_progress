<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Skill;
use App\Domain\Repository\SkillRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Skill> */
class SkillRepository extends ServiceEntityRepository implements SkillRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    public function findOneById(int $id): ?Skill
    {
        return $this->find($id);
    }

    public function findById(int $id): ?Skill
    {
        return $this->find($id);
    }

    /** @return Skill[] */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    public function findByTitle(string $title): ?Skill
    {
        return $this->findOneBy(['title' => $title]);
    }

    public function create(Skill $entity): int
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity->getId();
    }

    public function save(Skill $skill): void
    {
        $this->getEntityManager()->persist($skill);
        $this->getEntityManager()->flush();
    }

    public function delete(Skill $skill): void
    {
        $this->getEntityManager()->remove($skill);
        $this->getEntityManager()->flush();
    }
}
