<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Task;
use App\Domain\Entity\TaskSkill;

interface TaskRepositoryInterface
{
    public function findOneById(int $id): ?Task;

    public function findOneByIdWithLessonTasksAndSkills(int $id): ?Task;

    public function findById(int $id): ?Task;

    /** @return Task[] */
    public function findAll(): array;

    /** @return list<Task> */
    public function findAllWithCourse(): array;

    public function create(Task $entity): int;

    public function save(Task $task): void;

    public function delete(Task $task): void;

    public function findTaskSkill(int $taskId, int $skillId): ?TaskSkill;

    public function deleteTaskSkill(TaskSkill $taskSkill): void;
}
