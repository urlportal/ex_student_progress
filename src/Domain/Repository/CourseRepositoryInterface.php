<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Course;

interface CourseRepositoryInterface
{
    public function create(Course $entity): int;

    /** @return Course[] */
    public function searchByTitle(string $query): array;

    /** @return Course[] */
    public function findWithModules(): array;

    public function findById(int $id): ?Course;

    /** @return Course[] */
    public function findAll(): array;

    public function save(Course $course): void;

    public function delete(Course $course): void;

    public function findByTitle(string $title): ?Course;
}
