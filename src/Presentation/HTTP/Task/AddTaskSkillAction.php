<?php

namespace App\Presentation\HTTP\Task;

use App\Application\DTO\Request\AddTaskSkillRequestDTO;
use App\Application\DTO\Response\TaskSkillResponseDTO;
use App\Application\Service\TaskService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class AddTaskSkillAction
{
    public function __construct(
        private TaskService $taskService,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/v1/tasks/{taskId}/skills', name: 'api_v1_tasks_skills_add', methods: ['POST'])]
    public function __invoke(int $taskId, #[MapRequestPayload] AddTaskSkillRequestDTO $dto): Response
    {
        $taskSkill = $this->taskService->addSkill($taskId, $dto->skillId, $dto->weight);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(TaskSkillResponseDTO::fromTaskSkill($taskSkill), 'json'),
            Response::HTTP_CREATED
        );
    }
}
