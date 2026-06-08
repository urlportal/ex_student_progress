<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\TaskSkill;

final readonly class TaskSkillEntryDTO
{
    public function __construct(
        public int $skillId,
        public string $skillTitle,
        public float $weight,
    ) {
    }

    public static function fromTaskSkill(TaskSkill $taskSkill): self
    {
        return new self(
            skillId: $taskSkill->getSkill()->getId(),
            skillTitle: $taskSkill->getSkill()->getTitle(),
            weight: $taskSkill->getWeight(),
        );
    }
}
