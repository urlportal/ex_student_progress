<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\Module;

final readonly class ModuleResponseDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public int $courseId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Module $module): self
    {
        return new self(
            id: $module->getId(),
            title: $module->getTitle(),
            description: $module->getDescription(),
            courseId: $module->getCourse()->getId(),
            createdAt: $module->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $module->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
