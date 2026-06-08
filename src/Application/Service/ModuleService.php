<?php

namespace App\Application\Service;

use App\Application\DTO\Request\CreateModuleRequestDTO;
use App\Application\DTO\Request\UpdateModuleRequestDTO;
use App\Domain\Entity\Module;
use App\Domain\Exception\HasDependenciesException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\ModuleRepositoryInterface;

class ModuleService
{
    public function __construct(
        private readonly ModuleRepositoryInterface $moduleRepository,
        private readonly CourseService $courseService,
    ) {
    }

    public function find(int $id): Module
    {
        $module = $this->moduleRepository->findById($id);

        if (null === $module) {
            throw new NotFoundException();
        }

        return $module;
    }

    /** @return Module[] */
    public function findAll(): array
    {
        return $this->moduleRepository->findAll();
    }

    public function create(CreateModuleRequestDTO $dto): Module
    {
        $course = $this->courseService->find($dto->courseId);

        $module = new Module();
        $module->setTitle($dto->title);
        $module->setDescription($dto->description);
        $module->setCourse($course);

        $this->moduleRepository->save($module);

        return $module;
    }

    public function update(int $id, UpdateModuleRequestDTO $dto): Module
    {
        $module = $this->find($id);

        if (null !== $dto->title) {
            $module->setTitle($dto->title);
        }

        if (null !== $dto->description) {
            $module->setDescription($dto->description);
        }

        $this->moduleRepository->save($module);

        return $module;
    }

    public function delete(int $id): void
    {
        $module = $this->find($id);

        if ($module->getLessons()->count() > 0) {
            throw new HasDependenciesException();
        }

        $this->moduleRepository->delete($module);
    }
}
