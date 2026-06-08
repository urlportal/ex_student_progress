<?php

namespace App\Application\Service;

use App\Application\DTO\Response\StudentCourseScoreResponseDTO;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\AggStudentCourseRepositoryInterface;
use App\Domain\Repository\CourseRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AggStudentCourseService
{
    public function __construct(
        private readonly AggStudentCourseRepositoryInterface $aggRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CourseRepositoryInterface $courseRepository,
        #[Target('aggregationCache')] private readonly CacheInterface $cache,
    ) {
    }

    public function getByStudentAndCourse(string $studentId, int $courseId): StudentCourseScoreResponseDTO
    {
        try {
            return $this->cache->get(
                \sprintf('agg_student_course_%s_%s', $studentId, $courseId),
                function (ItemInterface $item) use ($studentId, $courseId): StudentCourseScoreResponseDTO {
                    $item->expiresAfter(3600);

                    if (null === $this->userRepository->findById($studentId)) {
                        throw new NotFoundException();
                    }

                    if (null === $this->courseRepository->findById($courseId)) {
                        throw new NotFoundException();
                    }

                    $entity = $this->aggRepository->findByStudentAndCourse($studentId, $courseId);

                    return null !== $entity
                        ? StudentCourseScoreResponseDTO::fromEntity($entity)
                        : StudentCourseScoreResponseDTO::empty($courseId);
                }
            );
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable) {
            // Redis недоступен — fallback на прямой запрос к БД
            if (null === $this->userRepository->findById($studentId)) {
                throw new NotFoundException();
            }

            if (null === $this->courseRepository->findById($courseId)) {
                throw new NotFoundException();
            }

            $entity = $this->aggRepository->findByStudentAndCourse($studentId, $courseId);

            return null !== $entity
                ? StudentCourseScoreResponseDTO::fromEntity($entity)
                : StudentCourseScoreResponseDTO::empty($courseId);
        }
    }
}
