<?php

namespace App\Presentation\HTTP\Task;

use App\Application\DTO\Response\TaskDetailResponseDTO;
use App\Application\Service\TaskService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class GetTaskAction
{
    public function __construct(
        private TaskService $taskService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/tasks/{id}', name: 'api_v1_tasks_get', methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $task = $this->taskService->find($id);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(TaskDetailResponseDTO::fromEntity($task), 'json'),
            Response::HTTP_OK
        );
    }
}
