<?php

namespace App\Presentation\HTTP\TaskExecution;

use App\Application\Service\TaskExecutionService;
use App\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class ListTaskExecutionsAction
{
    public function __construct(
        private TaskExecutionService $taskExecutionService,
        private SerializerInterface $serializer,
        private Security $security,
    ) {
    }

    #[Route('/api/v1/task-executions', name: 'api_v1_task_executions_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        $dtos = $this->taskExecutionService->findAllForUser($currentUser);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
