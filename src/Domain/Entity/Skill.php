<?php

namespace App\Domain\Entity;

use App\Domain\Trait\Timestamps;
use App\Infrastructure\Persistence\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ORM\UniqueConstraint(name: 'skill_title_unique', columns: ['title'])]
class Skill
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'smallint')]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $title;

    /**
     * @var Collection<int, TaskSkill>
     */
    #[ORM\OneToMany(
        targetEntity: TaskSkill::class,
        mappedBy: 'skill',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $skillTasks;

    public function __construct()
    {
        $this->skillTasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, TaskSkill>
     */
    public function getSkillTasks(): Collection
    {
        return $this->skillTasks;
    }

    public function addSkillTask(TaskSkill $skillTask): static
    {
        if (!$this->skillTasks->contains($skillTask)) {
            $this->skillTasks->add($skillTask);
            $skillTask->setSkill($this);
        }

        return $this;
    }

    public function removeSkillTask(TaskSkill $skillTask): static
    {
        if ($this->skillTasks->removeElement($skillTask)) {
            // set the owning side to null (unless already changed)
            if ($skillTask->getSkill() === $this) {
                $skillTask->setSkill(null);
            }
        }

        return $this;
    }
}
