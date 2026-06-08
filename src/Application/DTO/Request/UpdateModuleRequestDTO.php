<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateModuleRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        public ?string $description = null,
    ) {
    }
}
