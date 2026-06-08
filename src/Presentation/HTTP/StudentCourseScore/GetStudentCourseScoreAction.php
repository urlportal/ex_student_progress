<?php

namespace App\Presentation\HTTP\StudentCourseScore;

use App\Application\Service\AggStudentCourseService;
use App\Infrastructure\Security\Voter\AggStudentCourseVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final readonly class GetStudentCourseScoreAction
{
    public function __construct(
        private AggStudentCourseService $service,
        private SerializerInterface $serializer,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[Route('/api/v1/users/{userId}/courses/{courseId}/score', name: 'api_v1_users_courses_score', methods: ['GET'])]
    public function __invoke(string $userId, int $courseId): Response
    {
        if (!$this->authorizationChecker->isGranted(AggStudentCourseVoter::VIEW, $userId)) {
            throw new AccessDeniedException();
        }

        $dto = $this->service->getByStudentAndCourse($userId, $courseId);

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($dto, 'json'),
            Response::HTTP_OK
        );
    }
}
