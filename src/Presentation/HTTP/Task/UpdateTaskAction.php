<?php

namespace App\Presentation\HTTP\Task;

use App\Application\DTO\Request\UpdateTaskRequestDTO;
use App\Application\DTO\Response\TaskResponseDTO;
use App\Application\Service\TaskService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class UpdateTaskAction
{
    public function __construct(
        private TaskService $taskService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/tasks/{id}', name: 'api_v1_tasks_update', methods: ['PATCH'])]
    public function __invoke(int $id, #[MapRequestPayload] UpdateTaskRequestDTO $dto): Response
    {
        $task = $this->taskService->update($id, $dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(TaskResponseDTO::fromEntity($task), 'json'),
            Response::HTTP_OK
        );
    }
}
