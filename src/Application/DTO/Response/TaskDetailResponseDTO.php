<?php

namespace App\Application\DTO\Response;

use App\Domain\Entity\Task;

final readonly class TaskDetailResponseDTO
{
    /**
     * @param TaskSkillEntryDTO[] $skills
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public int $scoreMin,
        public int $scoreMax,
        public int $sort,
        public int $lessonId,
        public array $skills,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Task $task): self
    {
        $skills = array_map(
            static fn ($ts) => TaskSkillEntryDTO::fromTaskSkill($ts),
            $task->getTaskSkills()->toArray()
        );

        return new self(
            id: $task->getId(),
            title: $task->getTitle(),
            description: $task->getDescription(),
            scoreMin: $task->getScoreMin(),
            scoreMax: $task->getScoreMax(),
            sort: $task->getSort(),
            lessonId: $task->getLesson()->getId(),
            skills: $skills,
            createdAt: $task->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $task->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
