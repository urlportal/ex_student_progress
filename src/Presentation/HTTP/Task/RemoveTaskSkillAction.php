<?php

namespace App\Presentation\HTTP\Task;

use App\Application\Service\TaskService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class RemoveTaskSkillAction
{
    public function __construct(
        private TaskService $taskService,
    ) {
    }

    #[Route('/api/v1/tasks/{taskId}/skills/{skillId}', name: 'api_v1_tasks_skills_remove', methods: ['DELETE'])]
    public function __invoke(int $taskId, int $skillId): Response
    {
        $this->taskService->removeSkill($taskId, $skillId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
