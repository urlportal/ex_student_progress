<?php

namespace App\Domain\Entity;

use App\Domain\Trait\Timestamps;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity]
#[ORM\Index(name: 'agg_student_module_student_id_idx', columns: ['student_id'])]
#[ORM\Index(name: 'agg_student_module_module_id_idx', columns: ['module_id'])]
#[ORM\UniqueConstraint(
    name: 'agg_student_module_student_id_module_id_unique',
    columns: ['student_id', 'module_id']
)]
class AggStudentModule
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
    private Module $module;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0'])]
    private string $totalScore = '0';

    public function __construct(User $student, Module $module, float $totalScore = 0.0)
    {
        $this->student = $student;
        $this->module = $module;
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

    public function getModule(): Module
    {
        return $this->module;
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
