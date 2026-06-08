<?php

namespace App\Presentation\HTTP\StudentLessonSkillScore;

use App\Application\Service\AggStudentLessonSkillService;
use App\Infrastructure\Security\Voter\AggStudentLessonSkillVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class ListStudentLessonSkillScoresAction
{
    public function __construct(
        private AggStudentLessonSkillService $service,
        private SerializerInterface $serializer,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[Route('/api/v1/users/{userId}/lessons/{lessonId}/skill-scores', name: 'api_v1_users_lessons_skill_scores', methods: ['GET'])]
    public function __invoke(string $userId, int $lessonId): Response
    {
        if (!$this->authorizationChecker->isGranted(AggStudentLessonSkillVoter::VIEW, $userId)) {
            throw new AccessDeniedException();
        }

        $dtos = $this->service->getByStudentAndLesson($userId, $lessonId);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dtos, 'json'),
            Response::HTTP_OK
        );
    }
}
