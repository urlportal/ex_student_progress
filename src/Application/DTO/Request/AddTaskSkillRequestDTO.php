<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddTaskSkillRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public int $skillId,
        #[Assert\NotBlank]
        #[Assert\Range(min: 0, max: 100)]
        public float $weight,
    ) {
    }
}
