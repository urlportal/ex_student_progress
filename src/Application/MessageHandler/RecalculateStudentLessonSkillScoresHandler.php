<?php

namespace App\Application\MessageHandler;

use App\Application\Message\InvalidateCache;
use App\Application\Message\RecalculateStudentLessonSkillScores;
use App\Domain\Entity\AggStudentLessonSkill;
use App\Domain\Repository\AggStudentCourseRepositoryInterface;
use App\Domain\Repository\AggStudentLessonSkillRepositoryInterface;
use App\Domain\Repository\AggStudentModuleRepositoryInterface;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class RecalculateStudentLessonSkillScoresHandler
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskExecutionRepositoryInterface $taskExecutionRepository,
        private readonly AggStudentLessonSkillRepositoryInterface $aggRepository,
        private readonly AggStudentModuleRepositoryInterface $aggModuleRepository,
        private readonly AggStudentCourseRepositoryInterface $aggCourseRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RecalculateStudentLessonSkillScores $message): void
    {
        $task = $this->taskRepository->findOneByIdWithLessonTasksAndSkills($message->taskId);
        if (null === $task) {
            return;
        }

        $student = $this->userRepository->findById($message->studentId);
        if (null === $student) {
            return;
        }

        $lesson = $task->getLesson();

        $executions = $this->taskExecutionRepository->findByUserAndLesson(
            $message->studentId,
            (int) $lesson->getId()
        );

        /** @var array<int, int> $scoreByTask */
        $scoreByTask = [];
        foreach ($executions as $execution) {
            $scoreByTask[(int) $execution->getTask()->getId()] = $execution->getScore();
        }

        /** @var array<int, float> $skillScores */
        $skillScores = [];
        $skillMap = [];

        foreach ($lesson->getTasks() as $lessonTask) {
            $score = $scoreByTask[(int) $lessonTask->getId()] ?? 0;

            foreach ($lessonTask->getTaskSkills() as $taskSkill) {
                $skill = $taskSkill->getSkill();
                $skillId = (int) $skill->getId();
                $contribution = $score * $taskSkill->getWeight() / 100;

                if (!isset($skillScores[$skillId])) {
                    $skillScores[$skillId] = 0.0;
                    $skillMap[$skillId] = $skill;
                }
                $skillScores[$skillId] += $contribution;
            }
        }

        $entities = [];
        foreach ($skillScores as $skillId => $totalScore) {
            $entities[] = new AggStudentLessonSkill($student, $lesson, $skillMap[$skillId], $totalScore);
        }

        $this->aggRepository->replaceByStudentAndLesson(
            $message->studentId,
            (int) $lesson->getId(),
            $entities
        );

        $cacheKeys = [\sprintf('agg_student_lesson_skill_%s_%s', $message->studentId, (int) $lesson->getId())];

        $module = $lesson->getModule();
        if (null !== $module) {
            $moduleScore = (float) $this->taskExecutionRepository->sumScoreByStudentAndModule(
                $message->studentId,
                (int) $module->getId()
            );
            $this->aggModuleRepository->upsert($message->studentId, (int) $module->getId(), $moduleScore);
            $cacheKeys[] = \sprintf('agg_student_module_%s_%s', $message->studentId, (int) $module->getId());
        }

        $course = $lesson->getCourse();
        $courseScore = (float) $this->taskExecutionRepository->sumScoreByStudentAndCourse(
            $message->studentId,
            (int) $course->getId()
        );
        $this->aggCourseRepository->upsert($message->studentId, (int) $course->getId(), $courseScore);
        $cacheKeys[] = \sprintf('agg_student_course_%s_%s', $message->studentId, (int) $course->getId());

        try {
            $this->bus->dispatch(new InvalidateCache('aggregation_cache', $cacheKeys, 'student_lesson_skill_recalculation'));
        } catch (\Throwable $e) {
            $this->logger->warning('Cache invalidation dispatch failed', [
                'keys' => $cacheKeys,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
