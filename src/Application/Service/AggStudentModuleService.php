<?php

namespace App\Application\Service;

use App\Application\DTO\Response\StudentModuleScoreResponseDTO;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\AggStudentModuleRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AggStudentModuleService
{
    public function __construct(
        private readonly AggStudentModuleRepositoryInterface $aggRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ModuleRepositoryInterface $moduleRepository,
        #[Target('aggregationCache')] private readonly CacheInterface $cache,
    ) {
    }

    public function getByStudentAndModule(string $studentId, int $moduleId): StudentModuleScoreResponseDTO
    {
        try {
            return $this->cache->get(
                \sprintf('agg_student_module_%s_%s', $studentId, $moduleId),
                function (ItemInterface $item) use ($studentId, $moduleId): StudentModuleScoreResponseDTO {
                    $item->expiresAfter(3600);

                    if (null === $this->userRepository->findById($studentId)) {
                        throw new NotFoundException();
                    }

                    if (null === $this->moduleRepository->findById($moduleId)) {
                        throw new NotFoundException();
                    }

                    $entity = $this->aggRepository->findByStudentAndModule($studentId, $moduleId);

                    return null !== $entity
                        ? StudentModuleScoreResponseDTO::fromEntity($entity)
                        : StudentModuleScoreResponseDTO::empty($moduleId);
                }
            );
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable) {
            // Redis недоступен — fallback на прямой запрос к БД
            if (null === $this->userRepository->findById($studentId)) {
                throw new NotFoundException();
            }

            if (null === $this->moduleRepository->findById($moduleId)) {
                throw new NotFoundException();
            }

            $entity = $this->aggRepository->findByStudentAndModule($studentId, $moduleId);

            return null !== $entity
                ? StudentModuleScoreResponseDTO::fromEntity($entity)
                : StudentModuleScoreResponseDTO::empty($moduleId);
        }
    }
}
