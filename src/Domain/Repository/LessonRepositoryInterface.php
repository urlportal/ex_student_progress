<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Lesson;

interface LessonRepositoryInterface
{
    public function create(Lesson $entity): int;

    public function findById(int $id): ?Lesson;

    /** @return Lesson[] */
    public function findWithTasks(): array;

    /** @return Lesson[] */
    public function findAll(): array;

    public function save(Lesson $lesson): void;

    public function delete(Lesson $lesson): void;
}
