<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\AggStudentModule;

final readonly class StudentModuleScoreResponseDTO
{
    public function __construct(
        public int $moduleId,
        public float $totalScore,
    ) {
    }

    public static function fromEntity(AggStudentModule $entity): self
    {
        return new self(
            moduleId: (int) $entity->getModule()->getId(),
            totalScore: $entity->getTotalScore(),
        );
    }

    public static function empty(int $moduleId): self
    {
        return new self(moduleId: $moduleId, totalScore: 0.0);
    }
}
