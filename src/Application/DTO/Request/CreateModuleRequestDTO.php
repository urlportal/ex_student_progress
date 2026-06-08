<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateModuleRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
        #[Assert\NotBlank]
        public int $courseId,
        public ?string $description = null,
    ) {
    }
}
