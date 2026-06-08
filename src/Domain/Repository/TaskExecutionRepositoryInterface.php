<?php

namespace App\Domain\Repository;

use App\Domain\Entity\TaskExecution;

interface TaskExecutionRepositoryInterface
{
    public function save(TaskExecution $execution): void;

    public function findById(int $id): ?TaskExecution;

    public function findByUserAndTask(string $userId, int $taskId): ?TaskExecution;

    /** @return TaskExecution[] */
    public function findByUser(string $userId): array;

    /** @return TaskExecution[] */
    public function findByUserAndLesson(string $userId, int $lessonId): array;

    /** @return TaskExecution[] */
    public function findByTask(int $taskId): array;

    /** @return TaskExecution[] */
    public function findAll(): array;

    public function sumScoreByStudentAndModule(string $userId, int $moduleId): string;

    public function sumScoreByStudentAndCourse(string $userId, int $courseId): string;
}
