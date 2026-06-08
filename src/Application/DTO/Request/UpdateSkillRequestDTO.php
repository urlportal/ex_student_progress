<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateSkillRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        #[Assert\NotBlank(allowNull: true)]
        public ?string $title = null,
    ) {
    }
}
