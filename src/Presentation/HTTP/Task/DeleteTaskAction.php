<?php

namespace App\Presentation\HTTP\Task;

use App\Application\Service\TaskService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class DeleteTaskAction
{
    public function __construct(
        private TaskService $taskService,
    ) {
    }

    #[Route('/api/v1/tasks/{id}', name: 'api_v1_tasks_delete', methods: ['DELETE'])]
    public function __invoke(int $id): Response
    {
        $this->taskService->delete($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
