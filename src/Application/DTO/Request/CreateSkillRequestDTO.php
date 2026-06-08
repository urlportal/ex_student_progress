<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateSkillRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
    ) {
    }
}
