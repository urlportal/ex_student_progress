<?php

namespace App\Application\Service;

use App\Application\DTO\Request\CreateLessonRequestDTO;
use App\Application\DTO\Request\UpdateLessonRequestDTO;
use App\Domain\Entity\Lesson;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\InvalidRelationException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\LessonRepositoryInterface;

class LessonService
{
    public function __construct(
        private readonly LessonRepositoryInterface $lessonRepository,
        private readonly CourseService $courseService,
        private readonly ModuleService $moduleService,
    ) {
    }

    public function find(int $id): Lesson
    {
        $lesson = $this->lessonRepository->findById($id);

        if (null === $lesson) {
            throw new NotFoundException();
        }

        return $lesson;
    }

    /** @return Lesson[] */
    public function findAll(): array
    {
        return $this->lessonRepository->findAll();
    }

    public function create(CreateLessonRequestDTO $dto): Lesson
    {
        $course = $this->courseService->find($dto->courseId);

        $lesson = new Lesson();
        $lesson->setTitle($dto->title);
        $lesson->setDescription($dto->description);
        $lesson->setSort($dto->sort ?? 10000);
        $lesson->setIsActive($dto->isActive ?? true);
        $lesson->setCourse($course);

        if (null !== $dto->moduleId) {
            $module = $this->moduleService->find($dto->moduleId);

            if ($module->getCourse()->getId() !== $dto->courseId) {
                throw new InvalidRelationException('Module does not belong to the specified course');
            }

            $lesson->setModule($module);
        }

        $this->lessonRepository->save($lesson);

        return $lesson;
    }

    public function update(int $id, UpdateLessonRequestDTO $dto): Lesson
    {
        $lesson = $this->find($id);

        if (null !== $dto->title) {
            $lesson->setTitle($dto->title);
        }

        if (null !== $dto->description) {
            $lesson->setDescription($dto->description);
        }

        if (null !== $dto->sort) {
            $lesson->setSort($dto->sort);
        }

        if (null !== $dto->isActive) {
            $lesson->setIsActive($dto->isActive);
        }

        if (null !== $dto->moduleId) {
            $module = $this->moduleService->find($dto->moduleId);

            if ($module->getCourse()->getId() !== $lesson->getCourse()->getId()) {
                throw new InvalidRelationException('Module does not belong to the specified course');
            }

            $lesson->setModule($module);
        }

        $this->lessonRepository->save($lesson);

        return $lesson;
    }

    public function delete(int $id): void
    {
        $lesson = $this->find($id);

        if ($lesson->getTasks()->count() > 0) {
            throw new HasDependenciesException();
        }

        $this->lessonRepository->delete($lesson);
    }
}
