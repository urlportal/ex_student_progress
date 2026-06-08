<?php

namespace App\Presentation\HTTP\TaskExecution;

use App\Application\Service\TaskExecutionService;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\TaskExecutionRepositoryInterface;
use App\Infrastructure\Security\Voter\TaskExecutionVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class GetTaskExecutionAction
{
    public function __construct(
        private TaskExecutionService $taskExecutionService,
        private SerializerInterface $serializer,
        private TaskExecutionRepositoryInterface $taskExecutionRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[Route('/api/v1/task-executions/{id}', name: 'api_v1_task_executions_get', methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $execution = $this->taskExecutionRepository->findById($id);
        if (null === $execution) {
            throw new NotFoundException('Task execution not found');
        }

        if (!$this->authorizationChecker->isGranted(TaskExecutionVoter::VIEW, $execution)) {
            throw new AccessDeniedException();
        }

        $responseDto = $this->taskExecutionService->findById($id);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($responseDto, 'json'),
            Response::HTTP_OK
        );
    }
}
