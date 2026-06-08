<?php

namespace App\Domain\Repository;

use App\Domain\Entity\AggStudentLessonSkill;

interface AggStudentLessonSkillRepositoryInterface
{
    public function save(AggStudentLessonSkill $entity): void;

    /**
     * Вначале удаляет все записи для (studentId, lessonId) и сразу вставляет новые (в одном flush).
     *
     * @param AggStudentLessonSkill[] $entities
     */
    public function replaceByStudentAndLesson(string $studentId, int $lessonId, array $entities): void;

    /**
     * @return AggStudentLessonSkill[]
     */
    public function findByStudentAndLesson(string $studentId, int $lessonId): array;
}
