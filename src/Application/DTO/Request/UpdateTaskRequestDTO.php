<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTaskRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        #[Assert\NotBlank(allowNull: true)]
        public ?string $title = null,
        public ?string $description = null,
        public ?int $scoreMin = null,
        public ?int $scoreMax = null,
        public ?int $sort = null,
    ) {
    }
}
