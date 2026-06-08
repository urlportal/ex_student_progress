<?php

namespace App\Application\Message;

final readonly class RecalculateStudentLessonSkillScores
{
    public function __construct(
        public string $studentId,
        public int $taskId,
    ) {
    }
}
