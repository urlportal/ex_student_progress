<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\Course;

final readonly class CourseResponseDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public bool $isActive,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Course $course): self
    {
        return new self(
            id: $course->getId(),
            title: $course->getTitle(),
            description: $course->getDescription(),
            isActive: $course->isActive(),
            createdAt: $course->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $course->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
