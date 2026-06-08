<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\Lesson;

final readonly class LessonResponseDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public int $sort,
        public bool $isActive,
        public int $courseId,
        public ?int $moduleId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Lesson $lesson): self
    {
        return new self(
            id: $lesson->getId(),
            title: $lesson->getTitle(),
            description: $lesson->getDescription(),
            sort: $lesson->getSort(),
            isActive: $lesson->isActive(),
            courseId: $lesson->getCourse()->getId(),
            moduleId: $lesson->getModule()?->getId(),
            createdAt: $lesson->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $lesson->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
