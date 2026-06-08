<?php

namespace App\Presentation\HTTP\StudentModuleScore;

use App\Application\Service\AggStudentModuleService;
use App\Infrastructure\Security\Voter\AggStudentModuleVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class GetStudentModuleScoreAction
{
    public function __construct(
        private AggStudentModuleService $service,
        private SerializerInterface $serializer,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[Route('/api/v1/users/{userId}/modules/{moduleId}/score', name: 'api_v1_users_modules_score', methods: ['GET'])]
    public function __invoke(string $userId, int $moduleId): Response
    {
        if (!$this->authorizationChecker->isGranted(AggStudentModuleVoter::VIEW, $userId)) {
            throw new AccessDeniedException();
        }

        $dto = $this->service->getByStudentAndModule($userId, $moduleId);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dto, 'json'),
            Response::HTTP_OK
        );
    }
}
