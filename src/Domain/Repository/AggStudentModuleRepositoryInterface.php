<?php

namespace App\Domain\Repository;

use App\Domain\Entity\AggStudentModule;

interface AggStudentModuleRepositoryInterface
{
    public function upsert(string $studentId, int $moduleId, float $totalScore): void;

    public function findByStudentAndModule(string $studentId, int $moduleId): ?AggStudentModule;
}
