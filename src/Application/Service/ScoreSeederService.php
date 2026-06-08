<?php

namespace App\Application\Service;

use App\Application\Message\ScoreTask;
use App\Domain\Entity\Task;
use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;

class ScoreSeederService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{list<User>, array<int, list<Task>>}
     */
    public function loadData(): array
    {
        $students = $this->userRepository->findByRole(UserRole::STUDENT);

        $tasks = $this->taskRepository->findAllWithCourse();
        $tasksByCourse = [];
        foreach ($tasks as $task) {
            $courseId = (int) $task->getLesson()->getCourse()->getId();
            $tasksByCourse[$courseId][] = $task;
        }
        ksort($tasksByCourse);
        $tasksByCourse = array_values($tasksByCourse);

        return [$students, $tasksByCourse];
    }

    /**
     * @param list<User>             $students
     * @param array<int, list<Task>> $tasksByCourse
     */
    public function countTotal(array $students, array $tasksByCourse): int
    {
        $total = 0;
        $courseCount = \count($tasksByCourse);

        foreach ($students as $i => $student) {
            $indices = $this->getCourseIndices($i, $courseCount);
            foreach ($indices as $idx) {
                $total += \count($tasksByCourse[$idx] ?? []);
            }
        }

        return $total;
    }

    /**
     * @param list<User>             $students
     * @param array<int, list<Task>> $tasksByCourse
     *
     * @return array{int, int}
     */
    public function seed(array $students, array $tasksByCourse, ProgressBar $progress): array
    {
        $published = 0;
        $failed = 0;
        $courseCount = \count($tasksByCourse);

        foreach ($students as $i => $student) {
            $studentId = (string) $student->getId();
            $indices = $this->getCourseIndices($i, $courseCount);

            foreach ($indices as $idx) {
                foreach ($tasksByCourse[$idx] ?? [] as $task) {
                    try {
                        $this->messageBus->dispatch(new ScoreTask($studentId, (int) $task->getId(), random_int(1, 10)));
                        ++$published;
                        $progress->advance();
                    } catch (TransportException $e) {
                        $this->logger->warning('ScoreTask dispatch failed', [
                            'studentId' => $studentId,
                            'taskId' => $task->getId(),
                            'error' => $e->getMessage(),
                        ]);
                        ++$failed;
                    }
                }
            }
        }

        return [$published, $failed];
    }

    /**
     * @return list<int>
     */
    private function getCourseIndices(int $studentIndex, int $courseCount): array
    {
        if (0 === $courseCount) {
            return [];
        }

        $count = (2 === $studentIndex % 3) ? 3 : 2;
        $indices = [
            $studentIndex % $courseCount,
            ($studentIndex + 5) % $courseCount,
        ];

        if (3 === $count) {
            $indices[] = ($studentIndex + 8) % $courseCount;
        }

        return array_values(array_unique($indices));
    }
}
