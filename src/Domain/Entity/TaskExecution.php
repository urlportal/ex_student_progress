<?php

namespace App\Domain\Entity;

use App\Domain\Trait\Timestamps;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'task_execution_user_task_unique', columns: ['user_id', 'task_id'])]
#[ORM\Index(name: 'task_execution_user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'task_execution_task_id_idx', columns: ['task_id'])]
class TaskExecution
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'taskExecutions')]
    #[ORM\JoinColumn(nullable: false)]
    private Task $task;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $score;

    public function __construct(User $user, Task $task, int $score)
    {
        $this->user = $user;
        $this->task = $task;
        $this->score = $score;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }
}
