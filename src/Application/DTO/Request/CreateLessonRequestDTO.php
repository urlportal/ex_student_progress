<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateLessonRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
        #[Assert\NotBlank]
        public int $courseId,
        public ?string $description = null,
        #[Assert\Range(min: 0, max: 32767)]
        public ?int $sort = null,
        public ?bool $isActive = null,
        public ?int $moduleId = null,
    ) {
    }
}
