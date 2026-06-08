<?php

namespace App\Application\MessageHandler;

use App\Application\Message\RecalculateStudentLessonSkillScores;
use App\Application\Message\ScoreTask;
use App\Domain\Entity\TaskExecution;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ScoreTaskHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskExecutionRepositoryInterface $taskExecutionRepository,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(ScoreTask $message): void
    {
        $student = $this->userRepository->findById($message->studentId);
        if (null === $student) {
            return;
        }

        $task = $this->taskRepository->findOneById($message->taskId);
        if (null === $task) {
            return;
        }

        $execution = new TaskExecution($student, $task, $message->score);
        $this->taskExecutionRepository->save($execution);

        $this->bus->dispatch(new RecalculateStudentLessonSkillScores($message->studentId, $message->taskId));
    }
}
