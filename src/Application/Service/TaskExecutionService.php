<?php

namespace App\Application\Service;

use App\Application\DTO\Request\CreateTaskExecutionRequestDTO;
use App\Application\DTO\Response\TaskExecutionResponseDTO;
use App\Application\Message\RecalculateStudentLessonSkillScores;
use App\Domain\Entity\TaskExecution;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TaskExecutionService
{
    public function __construct(
        private readonly TaskExecutionRepositoryInterface $taskExecutionRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function create(CreateTaskExecutionRequestDTO $dto): TaskExecutionResponseDTO
    {
        $user = $this->userRepository->findById($dto->userId);
        if (null === $user) {
            throw new NotFoundException('User not found');
        }

        $task = $this->taskRepository->findOneById($dto->taskId);
        if (null === $task) {
            throw new NotFoundException('Task not found');
        }

        $existing = $this->taskExecutionRepository->findByUserAndTask($dto->userId, $dto->taskId);
        if (null !== $existing) {
            throw new AlreadyExistsException('Task execution already exists for this user and task');
        }

        $execution = new TaskExecution($user, $task, $dto->score);
        $this->taskExecutionRepository->save($execution);

        $this->messageBus->dispatch(new RecalculateStudentLessonSkillScores($dto->userId, $dto->taskId));

        return $this->toResponseDTO($execution);
    }

    public function findById(int $id): TaskExecutionResponseDTO
    {
        $execution = $this->taskExecutionRepository->findById($id);
        if (null === $execution) {
            throw new NotFoundException('Task execution not found');
        }

        return $this->toResponseDTO($execution);
    }

    /**
     * @return TaskExecutionResponseDTO[]
     */
    public function findAllForUser(User $currentUser): array
    {
        if ($currentUser->hasRole(UserRole::TEACHER) || $currentUser->hasRole(UserRole::ADMIN)) {
            $executions = $this->taskExecutionRepository->findAll();
        } else {
            $executions = $this->taskExecutionRepository->findByUser((string) $currentUser->getId());
        }

        return array_map($this->toResponseDTO(...), $executions);
    }

    public function updateScore(TaskExecution $execution, int $score): TaskExecutionResponseDTO
    {
        $execution->setScore($score);
        $this->taskExecutionRepository->save($execution);

        $this->messageBus->dispatch(new RecalculateStudentLessonSkillScores(
            (string) $execution->getUser()->getId(),
            (int) $execution->getTask()->getId()
        ));

        return $this->toResponseDTO($execution);
    }

    private function toResponseDTO(TaskExecution $execution): TaskExecutionResponseDTO
    {
        return new TaskExecutionResponseDTO(
            id: (int) $execution->getId(),
            taskId: $execution->getTask()->getId(),
            userId: (string) $execution->getUser()->getId(),
            score: $execution->getScore(),
            createdAt: $execution->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $execution->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
