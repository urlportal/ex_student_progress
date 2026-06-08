<?php

namespace App\Presentation\HTTP\TaskExecution;

use App\Application\DTO\Request\UpdateTaskExecutionRequestDTO;
use App\Application\Service\TaskExecutionService;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use App\Infrastructure\Security\Voter\TaskExecutionVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class UpdateTaskExecutionAction
{
    public function __construct(
        private TaskExecutionService $taskExecutionService,
        private SerializerInterface $serializer,
        private TaskExecutionRepositoryInterface $taskExecutionRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[Route('/api/v1/task-executions/{id}', name: 'api_v1_task_executions_update', methods: ['PATCH'])]
    public function __invoke(int $id, #[MapRequestPayload] UpdateTaskExecutionRequestDTO $dto): Response
    {
        $execution = $this->taskExecutionRepository->findById($id);
        if (null === $execution) {
            throw new NotFoundException('Task execution not found');
        }

        if (!$this->authorizationChecker->isGranted(TaskExecutionVoter::EDIT, $execution)) {
            throw new AccessDeniedException();
        }

        $responseDto = $this->taskExecutionService->updateScore($execution, $dto->score);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($responseDto, 'json'),
            Response::HTTP_OK
        );
    }
}
