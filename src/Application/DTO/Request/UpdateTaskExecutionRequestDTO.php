<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTaskExecutionRequestDTO
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\GreaterThanOrEqual(0)]
        #[Assert\LessThanOrEqual(32767)]
        public int $score,
    ) {
    }
}
