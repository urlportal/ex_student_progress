<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Skill;

interface SkillRepositoryInterface
{
    public function findOneById(int $id): ?Skill;

    public function findById(int $id): ?Skill;

    /** @return Skill[] */
    public function findAll(): array;

    public function findByTitle(string $title): ?Skill;

    public function create(Skill $entity): int;

    public function save(Skill $skill): void;

    public function delete(Skill $skill): void;
}
