<?php

namespace App\Application\Service;

use App\Application\DTO\Request\CreateCourseRequestDTO;
use App\Application\DTO\Request\UpdateCourseRequestDTO;
use App\Application\Message\CacheInvalidated;
use App\Application\Message\InvalidateCache;
use App\Domain\Entity\Course;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\CourseRepositoryInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[WithMonologChannel('app')]
class CourseService
{
    public function __construct(
        private readonly CourseRepositoryInterface $courseRepository,
        #[Target('listsCache')] private readonly CacheInterface $cache,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'app.cache.key.list_courses')] private readonly string $cacheKeyListCourses,
    ) {
    }

    public function find(int $id): Course
    {
        $course = $this->courseRepository->findById($id);

        if (null === $course) {
            throw new NotFoundException();
        }

        return $course;
    }

    /** @return Course[] */
    public function findAll(): array
    {
        try {
            return $this->cache->get($this->cacheKeyListCourses, function (ItemInterface $item): array {
                $item->expiresAfter(1800);

                return $this->courseRepository->findAll();
            });
        } catch (\Throwable) {
            return $this->courseRepository->findAll();
        }
    }

    public function create(CreateCourseRequestDTO $dto): Course
    {
        if (null !== $this->courseRepository->findByTitle($dto->title)) {
            throw new AlreadyExistsException();
        }

        $course = new Course();
        $course->setTitle($dto->title);
        $course->setDescription($dto->description);

        $this->courseRepository->save($course);

        $this->dispatchCacheInvalidation();

        return $course;
    }

    public function update(int $id, UpdateCourseRequestDTO $dto): Course
    {
        $course = $this->find($id);

        if (null !== $dto->title) {
            $existing = $this->courseRepository->findByTitle($dto->title);
            if (null !== $existing && $existing->getId() !== $course->getId()) {
                throw new AlreadyExistsException();
            }
            $course->setTitle($dto->title);
        }

        if (null !== $dto->description) {
            $course->setDescription($dto->description);
        }

        if (null !== $dto->isActive) {
            $course->setIsActive($dto->isActive);
        }

        $this->courseRepository->save($course);

        $this->dispatchCacheInvalidation();

        return $course;
    }

    public function delete(int $id): void
    {
        $course = $this->find($id);

        if ($course->getModules()->count() > 0 || $course->getLessons()->count() > 0) {
            throw new HasDependenciesException();
        }

        $this->courseRepository->delete($course);

        $this->dispatchCacheInvalidation();
    }

    private function dispatchCacheInvalidation(): void
    {
        $keys = [$this->cacheKeyListCourses];
        try {
            $this->bus->dispatch(new InvalidateCache('lists_cache', $keys, 'course_changed'));
            $this->bus->dispatch(
                new CacheInvalidated('course', $keys, time()),
                [new AmqpStamp('cache.invalidated.course')]
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Cache invalidation dispatch failed', [
                'keys' => $keys,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
