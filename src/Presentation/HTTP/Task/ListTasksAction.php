<?php

namespace App\Presentation\HTTP\Task;

use App\Application\DTO\Response\TaskResponseDTO;
use App\Application\Service\TaskService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class ListTasksAction
{
    public function __construct(
        private TaskService $taskService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/tasks', name: 'api_v1_tasks_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $tasks = $this->taskService->findAll();
        $dtos = array_map(static fn ($task) => TaskResponseDTO::fromEntity($task), $tasks);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
