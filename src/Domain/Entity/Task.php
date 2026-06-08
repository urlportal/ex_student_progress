<?php

namespace App\Domain\Entity;

use App\Domain\Trait\Timestamps;
use App\Infrastructure\Persistence\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Index('task_sort_idx', ['sort'])]
#[ORM\Index('task_lesson_id_idx', ['lesson_id'])]
class Task
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private Lesson $lesson;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
    private int $scoreMin = 1;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 10])]
    private int $scoreMax = 10;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 10000])]
    private int $sort = 10000;

    /**
     * @var Collection<int, TaskSkill>
     */
    #[ORM\OneToMany(
        targetEntity: TaskSkill::class,
        mappedBy: 'task',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $taskSkills;

    /**
     * @var Collection<int, TaskExecution>
     */
    #[ORM\OneToMany(targetEntity: TaskExecution::class, mappedBy: 'task')]
    private Collection $taskExecutions;

    public function __construct()
    {
        $this->taskSkills = new ArrayCollection();
        $this->taskExecutions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function setLesson(Lesson $lesson): static
    {
        $this->lesson = $lesson;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getScoreMin(): int
    {
        return $this->scoreMin;
    }

    public function setScoreMin(int $scoreMin): static
    {
        $this->scoreMin = $scoreMin;

        return $this;
    }

    public function getScoreMax(): int
    {
        return $this->scoreMax;
    }

    public function setScoreMax(int $scoreMax): static
    {
        $this->scoreMax = $scoreMax;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function addSkill(Skill $skill, float $weight): void
    {
        foreach ($this->taskSkills as $existing) {
            if ($existing->getSkill() === $skill) {
                return;
            }
        }
        $this->taskSkills->add(new TaskSkill($this, $skill, $weight));
    }

    /**
     * @return Collection<int, TaskSkill>
     */
    public function getTaskSkills(): Collection
    {
        return $this->taskSkills;
    }

    public function addTaskSkill(TaskSkill $taskSkill): static
    {
        if (!$this->taskSkills->contains($taskSkill)) {
            $this->taskSkills->add($taskSkill);
            $taskSkill->setTask($this);
        }

        return $this;
    }

    public function removeTaskSkill(TaskSkill $taskSkill): static
    {
        if ($this->taskSkills->removeElement($taskSkill)) {
            // set the owning side to null (unless already changed)
            if ($taskSkill->getTask() === $this) {
                $taskSkill->setTask(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TaskExecution>
     */
    public function getTaskExecutions(): Collection
    {
        return $this->taskExecutions;
    }
}
