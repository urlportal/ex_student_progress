<?php

namespace App\Presentation\HTTP\Task;

use App\Application\DTO\Request\CreateTaskRequestDTO;
use App\Application\DTO\Response\TaskResponseDTO;
use App\Application\Service\TaskService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class CreateTaskAction
{
    public function __construct(
        private TaskService $taskService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/tasks', name: 'api_v1_tasks_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateTaskRequestDTO $dto): Response
    {
        $task = $this->taskService->create($dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(TaskResponseDTO::fromEntity($task), 'json'),
            Response::HTTP_CREATED
        );
    }
}
