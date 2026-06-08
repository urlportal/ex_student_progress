<?php

namespace App\Application\DTO\Response;

final readonly class TaskExecutionResponseDTO
{
    public function __construct(
        public int $id,
        public int $taskId,
        public string $userId,
        public int $score,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
