<?php

namespace App\Application\Message;

final readonly class ScoreTask
{
    public function __construct(
        public string $studentId,
        public int $taskId,
        public int $score,
    ) {
    }
}
