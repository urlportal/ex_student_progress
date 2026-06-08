<?php

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTaskExecutionRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public string $userId,
        #[Assert\NotNull]
        #[Assert\GreaterThan(0)]
        public int $taskId,
        #[Assert\NotNull]
        #[Assert\GreaterThanOrEqual(0)]
        #[Assert\LessThanOrEqual(32767)]
        public int $score,
    ) {
    }
}
