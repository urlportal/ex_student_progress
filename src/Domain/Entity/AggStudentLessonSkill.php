<?php

namespace App\Domain\Entity;

use App\Domain\Trait\Timestamps;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity]
#[ORM\UniqueConstraint(
    name: 'agg_student_lesson_skill_student_id_lesson_id_skill_id_unique',
    columns: ['student_id', 'lesson_id', 'skill_id']
)]
class AggStudentLessonSkill
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $student;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Lesson $lesson;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Skill $skill;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0'])]
    private string $totalScore = '0';

    public function __construct(User $student, Lesson $lesson, Skill $skill, float $totalScore = 0.0)
    {
        $this->student = $student;
        $this->lesson = $lesson;
        $this->skill = $skill;
        $this->totalScore = (string) $totalScore;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): User
    {
        return $this->student;
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function getTotalScore(): float
    {
        return (float) $this->totalScore;
    }

    public function setTotalScore(float $totalScore): static
    {
        $this->totalScore = (string) $totalScore;

        return $this;
    }
}
