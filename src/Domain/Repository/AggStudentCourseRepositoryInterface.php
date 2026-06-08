<?php

namespace App\Domain\Repository;

use App\Domain\Entity\AggStudentCourse;

interface AggStudentCourseRepositoryInterface
{
    public function upsert(string $studentId, int $courseId, float $totalScore): void;

    public function findByStudentAndCourse(string $studentId, int $courseId): ?AggStudentCourse;
}
