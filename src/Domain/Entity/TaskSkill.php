<?php

namespace App\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'tasks_skills_unique', columns: ['task_id', 'skill_id'])]
#[ORM\Index('tasks_skills_task_id_idx', ['task_id'])]
#[ORM\Index('tasks_skills_skill_id_idx', ['skill_id'])]
class TaskSkill
{
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $weight;

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'taskSkills')]
        #[ORM\JoinColumn(nullable: false)]
        private Task $task,
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'skillTasks')]
        #[ORM\JoinColumn(nullable: false)]
        private Skill $skill,
        float $weight,
    ) {
        $this->weight = (string) $weight;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): static
    {
        $this->task = $task;

        return $this;
    }

    public function getSkill(): ?Skill
    {
        return $this->skill;
    }

    public function setSkill(?Skill $skill): static
    {
        $this->skill = $skill;

        return $this;
    }

    public function getWeight(): float
    {
        return (float) $this->weight;
    }

    public function setWeight(float $weight): static
    {
        $this->weight = (string) $weight;

        return $this;
    }
}
