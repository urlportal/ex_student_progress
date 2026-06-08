<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\AggStudentCourse;

final readonly class StudentCourseScoreResponseDTO
{
    public function __construct(
        public int $courseId,
        public float $totalScore,
    ) {
    }

    public static function fromEntity(AggStudentCourse $entity): self
    {
        return new self(
            courseId: (int) $entity->getCourse()->getId(),
            totalScore: $entity->getTotalScore(),
        );
    }

    public static function empty(int $courseId): self
    {
        return new self(courseId: $courseId, totalScore: 0.0);
    }
}
