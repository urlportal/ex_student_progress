<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\AggStudentLessonSkill;

final readonly class StudentLessonSkillScoreResponseDTO
{
    public function __construct(
        public int $skillId,
        public string $skillTitle,
        public float $totalScore,
    ) {
    }

    public static function fromEntity(AggStudentLessonSkill $entity): self
    {
        return new self(
            skillId: (int) $entity->getSkill()->getId(),
            skillTitle: $entity->getSkill()->getTitle(),
            totalScore: $entity->getTotalScore(),
        );
    }
}
