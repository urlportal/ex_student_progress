<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\TaskSkill;

final readonly class TaskSkillResponseDTO
{
    public function __construct(
        public int $taskId,
        public int $skillId,
        public float $weight,
    ) {
    }

    public static function fromTaskSkill(TaskSkill $taskSkill): self
    {
        return new self(
            taskId: $taskSkill->getTask()->getId(),
            skillId: $taskSkill->getSkill()->getId(),
            weight: $taskSkill->getWeight(),
        );
    }
}
