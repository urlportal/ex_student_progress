<?php

namespace App\Application\Service;

use App\Application\DTO\Request\CreateTaskRequestDTO;
use App\Application\DTO\Request\UpdateTaskRequestDTO;
use App\Domain\Entity\Task;
use App\Domain\Entity\TaskSkill;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\InvalidRelationException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\TaskRepositoryInterface;

class TaskService
{
    public const int MIN_TASK_SCORE = 1;
    public const int MAX_TASK_SCORE = 10;

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly LessonService $lessonService,
        private readonly SkillService $skillService,
    ) {
    }

    public function find(int $id): Task
    {
        $task = $this->taskRepository->findById($id);

        if (null === $task) {
            throw new NotFoundException();
        }

        return $task;
    }

    /** @return Task[] */
    public function findAll(): array
    {
        return $this->taskRepository->findAll();
    }

    public function create(CreateTaskRequestDTO $dto): Task
    {
        $lesson = $this->lessonService->find($dto->lessonId);

        $scoreMin = $dto->scoreMin ?? self::MIN_TASK_SCORE;
        $scoreMax = $dto->scoreMax ?? self::MAX_TASK_SCORE;

        $this->validateScores($scoreMin, $scoreMax);

        $task = new Task();
        $task->setTitle($dto->title);
        $task->setDescription($dto->description);
        $task->setScoreMin($scoreMin);
        $task->setScoreMax($scoreMax);
        $task->setSort($dto->sort ?? 10000);
        $task->setLesson($lesson);

        $this->taskRepository->save($task);

        return $task;
    }

    public function update(int $id, UpdateTaskRequestDTO $dto): Task
    {
        $task = $this->find($id);

        $effectiveMin = $dto->scoreMin ?? $task->getScoreMin();
        $effectiveMax = $dto->scoreMax ?? $task->getScoreMax();

        $this->validateScores($effectiveMin, $effectiveMax);

        if (null !== $dto->title) {
            $task->setTitle($dto->title);
        }

        if (null !== $dto->description) {
            $task->setDescription($dto->description);
        }

        if (null !== $dto->scoreMin) {
            $task->setScoreMin($dto->scoreMin);
        }

        if (null !== $dto->scoreMax) {
            $task->setScoreMax($dto->scoreMax);
        }

        if (null !== $dto->sort) {
            $task->setSort($dto->sort);
        }

        $this->taskRepository->save($task);

        return $task;
    }

    public function delete(int $id): void
    {
        $task = $this->find($id);

        $this->taskRepository->delete($task);
    }

    public function addSkill(int $taskId, int $skillId, float $weight): TaskSkill
    {
        $task = $this->find($taskId);

        foreach ($task->getTaskSkills() as $ts) {
            if ($ts->getSkill()->getId() === $skillId) {
                throw new AlreadyExistsException('Skill is already linked to this task');
            }
        }

        $skill = $this->skillService->find($skillId);

        $task->addSkill($skill, $weight);
        $this->taskRepository->save($task);

        foreach ($task->getTaskSkills() as $ts) {
            if ($ts->getSkill()->getId() === $skillId) {
                return $ts;
            }
        }

        throw new NotFoundException('TaskSkill not found after adding');
    }

    public function removeSkill(int $taskId, int $skillId): void
    {
        $this->find($taskId);

        $taskSkill = $this->taskRepository->findTaskSkill($taskId, $skillId);

        if (null === $taskSkill) {
            throw new NotFoundException();
        }

        $this->taskRepository->deleteTaskSkill($taskSkill);
    }

    private function validateScores(int $min, int $max): void
    {
        if ($min > $max) {
            throw new InvalidRelationException('scoreMin must be less than or equal to scoreMax');
        }
    }
}
