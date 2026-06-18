<?php

namespace App\Domain\Entity;

use App\Domain\Trait\Timestamps;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity]
#[ORM\Index(name: 'agg_student_course_student_id_idx', columns: ['student_id'])]
#[ORM\Index(name: 'agg_student_course_course_id_idx', columns: ['course_id'])]
#[ORM\UniqueConstraint(
    name: 'agg_student_course_student_id_course_id_unique',
    columns: ['student_id', 'course_id']
)]
class AggStudentCourse
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
    private Course $course;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0'])]
    private string $totalScore = '0';

    public function __construct(User $student, Course $course, float $totalScore = 0.0)
    {
        $this->student = $student;
        $this->course = $course;
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

    public function getCourse(): Course
    {
        return $this->course;
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
