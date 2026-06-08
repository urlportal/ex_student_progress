<?php

namespace App\Presentation\HTTP\TaskExecution;

use App\Application\DTO\Request\CreateTaskExecutionRequestDTO;
use App\Application\Service\TaskExecutionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class CreateTaskExecutionAction
{
    public function __construct(
        private TaskExecutionService $taskExecutionService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/task-executions', name: 'api_v1_task_executions_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateTaskExecutionRequestDTO $dto): Response
    {
        $responseDto = $this->taskExecutionService->create($dto);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($responseDto, 'json'),
            Response::HTTP_CREATED
        );
    }
}
