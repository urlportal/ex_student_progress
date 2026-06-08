<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Module;

interface ModuleRepositoryInterface
{
    public function create(Module $entity): int;

    /** @return Module[] */
    public function findWithLessons(): array;

    public function findById(int $id): ?Module;

    /** @return Module[] */
    public function findAll(): array;

    public function save(Module $module): void;

    public function delete(Module $module): void;
}
