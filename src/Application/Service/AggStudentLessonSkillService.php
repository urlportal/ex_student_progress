<?php

namespace App\Application\Service;

use App\Application\DTO\Response\StudentLessonSkillScoreResponseDTO;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\AggStudentLessonSkillRepositoryInterface;
use App\Domain\Repository\LessonRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AggStudentLessonSkillService
{
    public function __construct(
        private readonly AggStudentLessonSkillRepositoryInterface $aggRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LessonRepositoryInterface $lessonRepository,
        #[Target('aggregationCache')] private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return StudentLessonSkillScoreResponseDTO[]
     */
    public function getByStudentAndLesson(string $studentId, int $lessonId): array
    {
        try {
            return $this->cache->get(
                \sprintf('agg_student_lesson_skill_%s_%s', $studentId, $lessonId),
                function (ItemInterface $item) use ($studentId, $lessonId): array {
                    $item->expiresAfter(3600);

                    if (null === $this->userRepository->findById($studentId)) {
                        throw new NotFoundException('User not found');
                    }

                    if (null === $this->lessonRepository->findById($lessonId)) {
                        throw new NotFoundException('Lesson not found');
                    }

                    $entities = $this->aggRepository->findByStudentAndLesson($studentId, $lessonId);

                    return array_map(StudentLessonSkillScoreResponseDTO::fromEntity(...), $entities);
                }
            );
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Redis недоступен — fallback на прямой запрос к БД
            if (null === $this->userRepository->findById($studentId)) {
                throw new NotFoundException('User not found');
            }

            if (null === $this->lessonRepository->findById($lessonId)) {
                throw new NotFoundException('Lesson not found');
            }

            $entities = $this->aggRepository->findByStudentAndLesson($studentId, $lessonId);

            return array_map(StudentLessonSkillScoreResponseDTO::fromEntity(...), $entities);
        }
    }
}
