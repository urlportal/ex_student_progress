<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\Skill;

final readonly class SkillResponseDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Skill $skill): self
    {
        return new self(
            id: $skill->getId(),
            title: $skill->getTitle(),
            createdAt: $skill->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $skill->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
